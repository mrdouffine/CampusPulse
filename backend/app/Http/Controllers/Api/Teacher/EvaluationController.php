<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Evaluation;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Course $course, Request $request)
    {
        if ($course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($course->evaluations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'coefficient' => 'required|numeric|min:0.5',
            'date' => 'required|date',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        if ($course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evaluation = $course->evaluations()->create($validated);

        return response()->json($evaluation, 201);
    }
}
