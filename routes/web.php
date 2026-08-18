<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuickAttendanceController;

/*
|--------------------------------------------------------------------------
| Public Web Views
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('public.kiosk');
});

Route::get('/qdtr', function () {
    return view('public.kiosk');
});

Route::get('/login', function () {
    return view('public.login');
})->name('login');

Route::get('/register', function () {
    return view('public.register');
});

Route::get('/forgot-password', function () {
    return view('public.forgot_password');
});

/*
|--------------------------------------------------------------------------
| Student Web Views
|--------------------------------------------------------------------------
*/
Route::prefix('student')->group(function () {
    Route::get('/dashboard', function () { return view('student.dashboard'); });
    Route::get('/profile', function () { return view('student.profile'); });
    Route::get('/classroom', function () { return view('student.classroom'); });
    Route::get('/dtr', function () { return view('student.dtr'); });
    Route::get('/tasks', function () { return view('student.tasks'); });
    Route::get('/progress', function () { return view('student.progress'); });
});

/*
|--------------------------------------------------------------------------
| Teacher Web Views
|--------------------------------------------------------------------------
*/
Route::prefix('teacher')->group(function () {
    Route::get('/dashboard', function () { return view('teacher.dashboard'); });
    Route::get('/classrooms', function () { return view('teacher.classrooms'); });
    Route::get('/profile', function () { return view('teacher.profile'); });
    Route::get('/monitoring', function () { return view('teacher.monitoring'); });
    Route::get('/tasks/approvals', function () { return view('teacher.tasks_approvals'); });
});

/*
|--------------------------------------------------------------------------
| Public Direct API Wrappers
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('throttle:15,1')->group(function () {
    Route::post('/attendance/quick/record', [QuickAttendanceController::class, 'record']);
    Route::post('/attendance/quick/time-in', [QuickAttendanceController::class, 'timeIn']);
    Route::post('/attendance/quick/time-out', [QuickAttendanceController::class, 'timeOut']);
});
