<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Settings
        Setting::create(['key' => 'absence_threshold', 'value' => '20']);

        // 2. Admin
        $admin = User::create([
            'name' => 'Campus Admin',
            'email' => 'admin@campus pulse.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 3. Teachers
        $teacher1 = User::create([
            'name' => 'Prof. Alan Turing',
            'email' => 'turing@campuspulse.test',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);

        $teacher2 = User::create([
            'name' => 'Prof. Marie Curie',
            'email' => 'curie@campuspulse.test',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);

        // 4. Students (30 distinct students)
        $students = [];
        for ($i = 1; $i <= 30; $i++) {
            $students[] = User::create([
                'name' => "Student $i",
                'email' => "student$i@campuspulse.test",
                'password' => Hash::make('password'),
                'role' => 'student',
                'student_number' => 'CP' . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        // 5. Courses
        $course1 = Course::create([
            'name' => 'Introduction to Algorithms',
            'code' => 'CS101',
            'teacher_user_id' => $teacher1->id,
            'semester' => 'S1-2026',
            'total_hours' => 30,
        ]);

        $course2 = Course::create([
            'name' => 'Physics I',
            'code' => 'PHY101',
            'teacher_user_id' => $teacher2->id,
            'semester' => 'S1-2026',
            'total_hours' => 45,
        ]);

        // Enroll students in courses
        $studentIds = collect($students)->pluck('id');
        $course1->students()->attach($studentIds, ['enrolled_at' => now()]);

        // Enroll only half of the students in course 2
        $course2->students()->attach($studentIds->take(15), ['enrolled_at' => now()]);

        // 6. Course Sessions & Attendances for Course 1
        $sessionDates = [
            Carbon::now()->subDays(14),
            Carbon::now()->subDays(7),
            Carbon::now(),
        ];

        foreach ($sessionDates as $index => $date) {
            $session = CourseSession::create([
                'course_id' => $course1->id,
                'date' => $date->toDateString(),
                'topic' => "Algorithm Basics - Part " . ($index + 1),
                'duration_minutes' => 120,
            ]);

            // Randomize attendances
            foreach ($students as $student) {
                $status = 'present';
                $rand = rand(1, 10);
                if ($rand === 9)
                    $status = 'late';
                if ($rand === 10)
                    $status = 'absent';

                Attendance::create([
                    'course_session_id' => $session->id,
                    'user_id' => $student->id,
                    'status' => $status,
                ]);
            }
        }

        // 7. Evaluations & Grades for Course 1
        $evaluation = Evaluation::create([
            'course_id' => $course1->id,
            'title' => 'Midterm Exam',
            'coefficient' => 2.0,
            'date' => Carbon::now()->subDays(2)->toDateString(),
        ]);

        foreach ($students as $student) {
            Grade::create([
                'evaluation_id' => $evaluation->id,
                'user_id' => $student->id,
                'score' => rand(8, 20) + (rand(0, 9) / 10), // Random float score between 8 and 20.9
            ]);
        }
    }
}
