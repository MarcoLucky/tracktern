<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ClassroomService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected ClassroomService $classroomService;

    public function __construct(ClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }

    /**
     * User registration (Student or Teacher).
     * Auto-assigns 5-digit numeric intern_id for student with target_hours = 0.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:student,teacher',
            'department' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        if ($user->role === 'student') {
            $internId = $this->classroomService->generateUniqueInternId();

            $student = Student::create([
                'user_id' => $user->id,
                'intern_id' => $internId,
                'course_id' => null,
                'company_name' => null,
                'internship_start_date' => null,
                'internship_end_date' => null,
                'target_hours' => 0, // Target hours start at 0, set upon joining classroom
                'contact_number' => null,
            ]);
            $user->load('student.course');
        } else {
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'employee_number' => 'EMP-' . rand(1000, 9999),
                'department' => $validated['department'] ?? 'College of IT',
            ]);
            $user->load('teacher');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLogService::log(
            action: 'register',
            module: 'auth',
            userId: $user->id,
            payload: ['role' => $user->role]
        );

        return response()->json([
            'message' => 'Registration successful. Please login with your account.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    /**
     * User login.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower($credentials['email']))->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password credentials.'],
            ]);
        }

        Auth::login($user);
        $user->load(['student.course', 'teacher']);
        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLogService::log(
            action: 'login',
            module: 'auth',
            userId: $user->id
        );

        return response()->json([
            'message' => 'Login successful.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * User logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();

            AuditLogService::log(
                action: 'logout',
                module: 'auth',
                userId: $user->id
            );
        }

        Auth::guard('web')->logout();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Get current authenticated user details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['student.course', 'student.classrooms', 'teacher.classrooms']);

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Update profile details.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_number' => 'sometimes|nullable|string|max:20',
            'company_name' => 'sometimes|nullable|string|max:255',
            'internship_start_date' => 'sometimes|nullable|date',
            'internship_end_date' => 'sometimes|nullable|date|after_or_equal:internship_start_date',
            'target_hours' => 'sometimes|nullable|integer|min:0',
            'department' => 'sometimes|nullable|string|max:255',
        ]);

        if (isset($validated['name'])) {
            $user->update(['name' => $validated['name']]);
        }

        if ($user->isStudent() && $user->student) {
            $user->student->update(array_filter([
                'contact_number' => $validated['contact_number'] ?? $user->student->contact_number,
                'company_name' => $validated['company_name'] ?? $user->student->company_name,
                'internship_start_date' => $validated['internship_start_date'] ?? $user->student->internship_start_date,
                'internship_end_date' => $validated['internship_end_date'] ?? $user->student->internship_end_date,
                'target_hours' => $validated['target_hours'] ?? $user->student->target_hours,
            ]));
        } elseif ($user->isTeacher() && $user->teacher) {
            $user->teacher->update(array_filter([
                'department' => $validated['department'] ?? $user->teacher->department,
            ]));
        }

        $user->load(['student.course', 'teacher']);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Change Password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match our records.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        AuditLogService::log(
            action: 'change_password',
            module: 'auth',
            userId: $user->id
        );

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
