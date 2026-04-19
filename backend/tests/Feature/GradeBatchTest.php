<?php

use App\Models\Course;
use App\Models\Evaluation;
use App\Models\User;

it('allows a teacher to submit batch grades', function () {
    $teacher = User::create([
        'name' => 'Test Teacher',
        'email' => uniqid() . '@test.com',
        'password' => 'password',
        'role' => 'teacher'
    ]);
    $student1 = User::create([
        'name' => 'Student 1',
        'email' => uniqid() . '@test.com',
        'password' => 'password',
        'role' => 'student'
    ]);

    $course = Course::create([
        'name' => 'Physics',
        'code' => uniqid(),
        'teacher_user_id' => $teacher->id,
        'semester' => 'S1-2026',
        'total_hours' => 20
    ]);
    $course->students()->attach([$student1->id]);

    $evaluation = Evaluation::create([
        'course_id' => $course->id,
        'title' => 'Final Exam',
        'coefficient' => 2.0,
        'date' => now()
    ]);

    $response = $this->actingAs($teacher)->postJson("/api/teacher/evaluations/{$evaluation->id}/grades", [
        'grades' => [
            ['user_id' => $student1->id, 'score' => 18.5],
        ],
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('grades', ['user_id' => $student1->id, 'score' => '18.50']);
});
