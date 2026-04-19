<?php

use App\Models\User;

it('prevents students from accessing admin routes', function () {
    $student = User::create([
        'name' => 'Test Student',
        'email' => uniqid() . '@test.com',
        'password' => 'password',
        'role' => 'student'
    ]);

    $response = $this->actingAs($student)->getJson("/api/admin/users");

    // CheckRole middleware returns 403
    $response->assertStatus(403);
});

it('prevents teachers from accessing student routes', function () {
    $teacher = User::create([
        'name' => 'Test Teacher',
        'email' => uniqid() . '@test.com',
        'password' => 'password',
        'role' => 'teacher'
    ]);

    $response = $this->actingAs($teacher)->getJson("/api/student/dashboard");

    // CheckRole middleware returns 403
    $response->assertStatus(403);
});

it('allows admin to access admin routes', function () {
    $admin = User::create([
        'name' => 'Test Admin',
        'email' => uniqid() . '@test.com',
        'password' => 'password',
        'role' => 'admin'
    ]);

    $response = $this->actingAs($admin)->getJson("/api/admin/users");

    $response->assertStatus(200);
});
