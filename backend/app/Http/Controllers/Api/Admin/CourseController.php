<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index()
    {
        return response()->json(Course::with('teacher')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses',
            'teacher_user_id' => 'required|exists:users,id',
            'semester' => 'required|string|max:20',
            'total_hours' => 'required|integer|min:1',
        ]);

        // Ensure teacher_user_id belongs to a teacher
        $teacher = User::findOrFail($validated['teacher_user_id']);
        if ($teacher->role !== 'teacher') {
            return response()->json(['message' => 'The assigned user is not a teacher.'], 422);
        }

        $course = Course::create($validated);

        return response()->json($course, 201);
    }

    public function show(Course $course)
    {
        return response()->json($course->load(['teacher', 'students']));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('courses')->ignore($course->id)],
            'teacher_user_id' => 'sometimes|exists:users,id',
            'semester' => 'sometimes|string|max:20',
            'total_hours' => 'sometimes|integer|min:1',
        ]);

        if (isset($validated['teacher_user_id'])) {
            $teacher = User::findOrFail($validated['teacher_user_id']);
            if ($teacher->role !== 'teacher') {
                return response()->json(['message' => 'The assigned user is not a teacher.'], 422);
            }
        }

        $course->update($validated);

        return response()->json($course);
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return response()->json(null, 204);
    }

    public function enroll(Request $request, Course $course)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        // Enroll only users with role student
        $students = User::whereIn('id', $validated['student_ids'])->where('role', 'student')->pluck('id');

        // Sync without detaching avoids deleting existing students
        $course->students()->syncWithoutDetaching($students);

        return response()->json(['message' => 'Students enrolled successfully.', 'enrolled_count' => $students->count()]);
    }
}
