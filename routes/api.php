<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuickAttendanceController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentTaskController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentMonitoringController;
use App\Http\Controllers\TaskApprovalController;
use App\Http\Controllers\TeacherReportController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Quick Public Attendance Kiosk (Rate Limited)
    Route::middleware('throttle:15,1')->prefix('attendance/quick')->group(function () {
        Route::post('/time-in', [QuickAttendanceController::class, 'timeIn']);
        Route::post('/time-out', [QuickAttendanceController::class, 'timeOut']);
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes (Sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        // Shared User Profile & Auth
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | Student Role Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:student')->prefix('student')->group(function () {
            Route::get('/dashboard', [StudentDashboardController::class, 'index']);

            // Attendance / DTR
            Route::get('/dtr', [StudentAttendanceController::class, 'index']);
            Route::post('/dtr/time-in', [StudentAttendanceController::class, 'timeIn']);
            Route::post('/dtr/time-out', [StudentAttendanceController::class, 'timeOut']);

            // Classroom
            Route::get('/classroom', [ClassroomController::class, 'index']);
            Route::post('/classroom/join', [ClassroomController::class, 'join']);

            // Tasks
            Route::get('/tasks', [StudentTaskController::class, 'index']);
            Route::post('/tasks', [StudentTaskController::class, 'store']);
            Route::get('/tasks/{id}', [StudentTaskController::class, 'show']);

            // Progress & PDF Exports
            Route::get('/progress', [StudentProgressController::class, 'index']);
            Route::get('/reports/dtr/export', [StudentProgressController::class, 'exportDtr']);
            Route::get('/reports/progress/export', [StudentProgressController::class, 'exportProgress']);
        });

        /*
        |--------------------------------------------------------------------------
        | Teacher Role Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:teacher')->prefix('teacher')->group(function () {
            Route::get('/dashboard', [TeacherDashboardController::class, 'index']);

            // Classrooms CRUD & Nested Roster / Tasks Queue
            Route::get('/classrooms', [ClassroomController::class, 'index']);
            Route::post('/classrooms', [ClassroomController::class, 'store']);
            Route::get('/classrooms/{id}', [ClassroomController::class, 'show']);
            Route::put('/classrooms/{id}', [ClassroomController::class, 'update']);
            Route::delete('/classrooms/{id}', [ClassroomController::class, 'destroy']);

            // Student Monitoring & Task Approvals
            Route::get('/monitoring', [StudentMonitoringController::class, 'index']);
            Route::get('/monitoring/student/{studentId}', [StudentMonitoringController::class, 'showStudent']);
            Route::get('/tasks/approvals', [TaskApprovalController::class, 'index']);
            Route::post('/tasks/{id}/approve', [TaskApprovalController::class, 'approve']);
            Route::post('/tasks/{id}/revision', [TaskApprovalController::class, 'requestRevision']);

            // PDF / Data Export Center
            Route::get('/reports/classroom/{classroomId}/export', [TeacherReportController::class, 'exportClassroom']);
            Route::get('/reports/student/{studentId}/export', [TeacherReportController::class, 'exportStudentDossier']);
        });
    });
});
