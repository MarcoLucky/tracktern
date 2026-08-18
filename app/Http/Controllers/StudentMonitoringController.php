<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Classroom;
use App\Services\RenderedHoursService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentMonitoringController extends Controller
{
    protected RenderedHoursService $renderedHoursService;

    public function __construct(RenderedHoursService $renderedHoursService)
    {
        $this->renderedHoursService = $renderedHoursService;
    }

    /**
     * Teacher monitoring endpoint: View student roster progress across classrooms.
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $classrooms = $teacher->classrooms()->with(['students.user', 'course'])->get();

        $studentsMonitoring = [];

        foreach ($classrooms as $classroom) {
            foreach ($classroom->students as $student) {
                $progress = $this->renderedHoursService->calculateStudentProgress($student, $classroom->id);
                $progress['classroom_id'] = $classroom->id;
                $progress['classroom_name'] = $classroom->classroom_name;
                $studentsMonitoring[] = $progress;
            }
        }

        return response()->json([
            'total_students' => count($studentsMonitoring),
            'students' => $studentsMonitoring,
        ]);
    }

    /**
     * View detailed student profile, DTR, and submissions for a teacher.
     */
    public function showStudent(Request $request, int $studentId): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher profile required.'], 403);
        }

        $student = Student::with(['user', 'course', 'attendance', 'tasks.attachments', 'weeklyReports.attachments'])
            ->whereHas('classrooms', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->findOrFail($studentId);

        $progress = $this->renderedHoursService->calculateStudentProgress($student);

        return response()->json([
            'student' => $student,
            'progress' => $progress,
        ]);
    }
}
