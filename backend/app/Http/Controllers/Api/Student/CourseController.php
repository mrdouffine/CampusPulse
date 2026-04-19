<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->coursesEnrolled);
    }

    public function show(Course $course, Request $request)
    {
        if (!$course->students()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'course' => $course,
            'attendances' => $request->user()->attendances()->whereHas('session', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })->with('session')->get(),
            'grades' => $request->user()->grades()->whereHas('evaluation', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })->with('evaluation')->get(),
        ]);
    }
}
