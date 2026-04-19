<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->coursesTaught()->withCount('students')->get());
    }

    public function show(Course $course, Request $request)
    {
        if ($course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($course->load(['students', 'sessions', 'evaluations']));
    }

    public function stats(Course $course, Request $request)
    {
        if ($course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'total_students' => $course->students()->count(),
            'total_sessions' => $course->sessions()->count(),
            'total_evaluations' => $course->evaluations()->count(),
        ]);
    }
}
