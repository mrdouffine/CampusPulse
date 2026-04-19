<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Evaluation $evaluation, Request $request)
    {
        if ($evaluation->course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($evaluation->load('grades.student'));
    }

    public function store(Request $request, Evaluation $evaluation)
    {
        if ($evaluation->course->teacher_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.user_id' => 'required|exists:users,id',
            'grades.*.score' => 'required|numeric|min:0|max:20',
        ]);

        foreach ($validated['grades'] as $data) {
            $evaluation->grades()->updateOrCreate(
                ['user_id' => $data['user_id']],
                ['score' => $data['score']]
            );
        }

        return response()->json(['message' => 'Grades saved successfully.', 'grades' => $evaluation->grades()->get()]);
    }
}
