<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Course $course, Request $request)
    {
        if ($course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($course->sessions);
    }

    public function store(Request $request, Course $course)
    {
        if ($course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'topic' => 'nullable|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
        ]);

        $session = $course->sessions()->create($validated);

        // Pre-fill all enrolled students as 'present' automatically to save time (from Cahier des Charges)
        $students = $course->students;
        $attendances = [];
        $now = now();
        foreach ($students as $student) {
            $attendances[] = [
                'course_session_id' => $session->id,
                'user_id' => $student->id,
                'status' => 'present',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        \App\Models\Attendance::insert($attendances);

        return response()->json($session->load('attendances'), 201);
    }
}
