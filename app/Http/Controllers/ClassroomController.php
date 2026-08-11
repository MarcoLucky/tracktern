<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\ClassroomService;
use App\Services\AuditLogService;
use App\Services\RenderedHoursService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class ClassroomController extends Controller
{
    protected ClassroomService $classroomService;
    protected RenderedHoursService $renderedHoursService;

    public function __construct(ClassroomService $classroomService, RenderedHoursService $renderedHoursService)
    {
        $this->classroomService = $classroomService;
        $this->renderedHoursService = $renderedHoursService;
    }

    /**
     * List classrooms.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isTeacher() && $user->teacher) {
            $classrooms = $user->teacher->classrooms()->with(['course', 'students'])->get();
            return response()->json($classrooms);
        }

        if ($user->isStudent() && $user->student) {
            $classrooms = $user->student->classrooms()->with(['teacher.user', 'course'])->get();
            return response()->json($classrooms);
        }

        return response()->json([], 400);
    }

    /**
     * Teacher create classroom (C in CRUD).
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $validated = $request->validate([
            'classroom_name' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'required_hours' => 'nullable|integer|min:1',
            'semester' => 'nullable|string|max:100',
            'academic_year' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $classroomCode = $this->classroomService->generateUniqueClassroomCode();

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'course_id' => $validated['course_id'] ?? null,
            'classroom_name' => $validated['classroom_name'],
            'classroom_code' => $classroomCode,
            'required_hours' => $validated['required_hours'] ?? 400,
            'semester' => $validated['semester'] ?? 'First Semester',
            'academic_year' => $validated['academic_year'] ?? date('Y') . '-' . (date('Y') + 1),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        $classroom->load(['course', 'teacher.user']);

        AuditLogService::log(
            action: 'create_classroom',
            module: 'classroom',
            userId: $teacher->user_id,
            recordId: $classroom->id,
            payload: ['code' => $classroomCode]
        );

        return response()->json([
            'message' => 'Classroom created successfully.',
            'classroom' => $classroom,
        ], 201);
    }

    /**
     * View detailed classroom (R in CRUD) with nested Student Monitoring & Task Approval Queue.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $classroom = Classroom::with(['teacher.user', 'course', 'students.user', 'tasks.student.user', 'tasks.attachments'])->findOrFail($id);

        // Calculate student monitoring progress for enrolled students
        $studentsMonitoring = $classroom->students->map(function ($student) use ($classroom) {
            return $this->renderedHoursService->calculateStudentProgress($student, $classroom->id);
        });

        // Filter pending tasks for this classroom
        $pendingTasks = $classroom->tasks->where('status', 'pending')->values();

        return response()->json([
            'classroom' => $classroom,
            'student_monitoring' => $studentsMonitoring,
            'pending_tasks' => $pendingTasks,
        ]);
    }

    /**
     * Update classroom details (U in CRUD).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $classroom = Classroom::where('teacher_id', $teacher->id)->findOrFail($id);

        $validated = $request->validate([
            'classroom_name' => 'sometimes|string|max:255',
            'required_hours' => 'sometimes|integer|min:1',
            'semester' => 'sometimes|string|max:100',
            'academic_year' => 'sometimes|string|max:100',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
        ]);

        $classroom->update($validated);

        AuditLogService::log(
            action: 'update_classroom',
            module: 'classroom',
            userId: $teacher->user_id,
            recordId: $classroom->id
        );

        return response()->json([
            'message' => 'Classroom updated successfully.',
            'classroom' => $classroom,
        ]);
    }

    /**
     * Delete classroom (D in CRUD).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user()->teacher;

        $classroom = Classroom::where('teacher_id', $teacher->id)->findOrFail($id);
        $classroom->delete();

        AuditLogService::log(
            action: 'delete_classroom',
            module: 'classroom',
            userId: $teacher->user_id,
            recordId: $id
        );

        return response()->json([
            'message' => 'Classroom deleted successfully.',
        ]);
    }

    /**
     * Student join classroom using 5-digit classroom code.
     */
    public function join(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student profile required.'], 403);
        }

        $validated = $request->validate([
            'classroom_code' => 'required|string|size:5',
        ]);

        try {
            $classroom = $this->classroomService->joinClassroom($student, $validated['classroom_code']);

            return response()->json([
                'message' => 'Successfully joined classroom ' . $classroom->classroom_name,
                'classroom' => $classroom,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
