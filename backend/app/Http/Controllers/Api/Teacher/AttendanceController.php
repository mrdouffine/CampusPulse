<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(CourseSession $session, Request $request)
    {
        if ($session->course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($session->load('attendances.student'));
    }

    public function store(Request $request, CourseSession $session)
    {
        if ($session->course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => ['required', Rule::in(['present', 'absent', 'late'])],
            'attendances.*.note' => 'nullable|string',
        ]);

        foreach ($validated['attendances'] as $data) {
            $session->attendances()->updateOrCreate(
                ['user_id' => $data['user_id']],
                ['status' => $data['status'], 'note' => $data['note'] ?? null]
            );
        }

        return response()->json(['message' => 'Attendances saved successfully.', 'attendances' => $session->attendances()->get()]);
    }
}
