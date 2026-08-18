<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ClassroomService;
use App\Services\AuditLogService;
use App\Services\PhpMailerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected ClassroomService $classroomService;
    protected PhpMailerService $mailerService;

    public function __construct(ClassroomService $classroomService, PhpMailerService $mailerService)
    {
        $this->classroomService = $classroomService;
        $this->mailerService = $mailerService;
    }

    /**
     * Send a 6-digit OTP to the user's email for password reset verification.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email|exists:users,email',
        ]);

        $email = strtolower($validated['email']);
        $user = User::where('email', $email)->firstOrFail();
        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        $this->mailerService->sendPasswordResetOtp($user, $otp);

        AuditLogService::log(
            action: 'forgot_password_otp_requested',
            module: 'auth',
            userId: $user->id
        );

        return response()->json([
            'message' => 'We sent a password reset OTP to your email address.',
        ]);
    }

    /**
     * Verify password reset OTP and set a new password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower($validated['email']);
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'otp' => ['Please request a new OTP code before resetting your password.'],
            ]);
        }

        if (!$record->created_at || Carbon::parse($record->created_at)->lt(now()->subMinutes(15))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw ValidationException::withMessages([
                'otp' => ['This OTP code has expired. Please request a new code.'],
            ]);
        }

        if (!Hash::check($validated['otp'], $record->token)) {
            throw ValidationException::withMessages([
                'otp' => ['The OTP code is invalid.'],
            ]);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        AuditLogService::log(
            action: 'forgot_password_reset_completed',
            module: 'auth',
            userId: $user->id
        );

        return response()->json([
            'message' => 'Password reset successfully. Please login with your new password.',
        ]);
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
                'email' => ['Invalid email address or password.'],
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
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'contact_number' => 'sometimes|nullable|string|max:20',
            'company_name' => 'sometimes|nullable|string|max:255',
            'organization_location' => 'sometimes|nullable|string|max:255',
            'internship_start_date' => 'sometimes|nullable|date',
            'internship_end_date' => 'sometimes|nullable|date|after_or_equal:internship_start_date',
            'target_hours' => 'sometimes|nullable|integer|min:0',
            'department' => 'sometimes|nullable|string|max:255',
        ]);

        if (isset($validated['name'])) {
            $user->update(['name' => $validated['name']]);
        }

        if (isset($validated['email'])) {
            $user->update(['email' => strtolower($validated['email'])]);
        }

        if ($user->isStudent() && $user->student) {
            $studentUpdates = [];

            foreach (['contact_number', 'company_name', 'organization_location', 'internship_start_date', 'internship_end_date', 'target_hours'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $studentUpdates[$field] = $validated[$field];
                }
            }

            if ($studentUpdates) {
                $user->student->update($studentUpdates);
            }
        } elseif ($user->isTeacher() && $user->teacher) {
            $teacherUpdates = [];

            foreach (['contact_number', 'department'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $teacherUpdates[$field] = $validated[$field];
                }
            }

            if ($teacherUpdates) {
                $user->teacher->update($teacherUpdates);
            }
        }

        $user->load(['student.course', 'teacher']);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Upload or replace the authenticated student's profile photo.
     */
    public function updateProfilePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStudent() || !$user->student) {
            return response()->json(['message' => 'Student profile required.'], 403);
        }

        $validated = $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($user->student->profile_photo_path) {
            Storage::disk('public')->delete($user->student->profile_photo_path);
        }

        $path = $validated['profile_photo']->store('profile_photos', 'public');
        $user->student->update([
            'profile_photo_path' => $path,
        ]);

        $user->load(['student.course', 'teacher']);

        return response()->json([
            'message' => 'Profile photo updated successfully.',
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
