<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// Admin Routes (middleware: auth + admin)
// ==========================================
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('users', \App\Http\Controllers\Api\Admin\UserController::class);
    Route::apiResource('courses', \App\Http\Controllers\Api\Admin\CourseController::class);
    // Batch enroll students
    Route::post('courses/{course}/enroll', [\App\Http\Controllers\Api\Admin\CourseController::class, 'enroll']);

    Route::get('reports', [\App\Http\Controllers\Api\Admin\ReportController::class, 'index']);
    Route::get('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'index']);
    Route::put('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'update']);
});

// ==========================================
// Teacher Routes (middleware: auth + teacher)
// ==========================================
Route::middleware(['auth:sanctum', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::apiResource('courses', \App\Http\Controllers\Api\Teacher\CourseController::class)->only(['index', 'show']);

    // Sessions nested in courses
    Route::get('courses/{course}/sessions', [\App\Http\Controllers\Api\Teacher\SessionController::class, 'index']);
    Route::post('courses/{course}/sessions', [\App\Http\Controllers\Api\Teacher\SessionController::class, 'store']);

    // Batch Attendance
    Route::get('sessions/{session}/attendance', [\App\Http\Controllers\Api\Teacher\AttendanceController::class, 'index']);
    Route::post('sessions/{session}/attendance', [\App\Http\Controllers\Api\Teacher\AttendanceController::class, 'store']);

    // Evaluations
    Route::get('courses/{course}/evaluations', [\App\Http\Controllers\Api\Teacher\EvaluationController::class, 'index']);
    Route::post('evaluations', [\App\Http\Controllers\Api\Teacher\EvaluationController::class, 'store']);

    // Batch Grades
    Route::get('evaluations/{evaluation}/grades', [\App\Http\Controllers\Api\Teacher\GradeController::class, 'index']);
    Route::post('evaluations/{evaluation}/grades', [\App\Http\Controllers\Api\Teacher\GradeController::class, 'store']);

    // Stats
    Route::get('courses/{course}/stats', [\App\Http\Controllers\Api\Teacher\CourseController::class, 'stats']);
});

// ==========================================
// Student Routes (middleware: auth + student)
// ==========================================
Route::middleware(['auth:sanctum', 'role:student'])->prefix('student')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Api\Student\DashboardController::class, 'index']);
    Route::apiResource('courses', \App\Http\Controllers\Api\Student\CourseController::class)->only(['index', 'show']);
    Route::get('grades', [\App\Http\Controllers\Api\Student\GradeController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
