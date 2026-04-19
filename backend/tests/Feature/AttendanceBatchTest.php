<?php

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;

it('allows a teacher to submit batch attendance', function () {
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
        'name' => 'Math 101',
        'code' => uniqid(),
        'teacher_user_id' => $teacher->id,
        'semester' => 'S1-2026',
        'total_hours' => 20
    ]);
    $course->students()->attach([$student1->id]);

    $session = CourseSession::create([
        'course_id' => $course->id,
        'date' => now(),
        'duration_minutes' => 120
    ]);

    $response = $this->actingAs($teacher)->postJson("/api/teacher/sessions/{$session->id}/attendance", [
        'attendances' => [
            ['user_id' => $student1->id, 'status' => 'present', 'note' => null],
        ],
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('attendances', ['user_id' => $student1->id, 'status' => 'present']);
});
