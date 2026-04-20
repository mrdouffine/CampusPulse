<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Session;
use App\Models\Attendance;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration initiale
        Setting::set('absence_threshold', 30);
        Setting::set('late_weight', 0.5);

        // Créer un étudiant et un cours
        $this->student = User::factory()->create(['role' => 'student']);
        $this->course = Course::factory()->create();
        
        // Inscrire l'étudiant au cours
        $this->course->users()->attach($this->student->id);
    }

    public function test_global_attendance_rate_accessor(): void
    {
        // Créer des présences variées
        $sessions = Session::factory()->count(4)->create(['course_id' => $this->course->id]);
        
        $attendances = [
            ['status' => 'present'],
            ['status' => 'present'],
            ['status' => 'late'],
            ['status' => 'absent'],
        ];

        foreach ($sessions as $i => $session) {
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $attendances[$i]['status'],
            ]);
        }

        $rate = $this->student->global_attendance_rate;

        // (2 présents + 0.5 retard) / 4 = 62.5%
        $this->assertEquals(62.5, $rate);
    }

    public function test_formatted_global_attendance_rate(): void
    {
        $this->student->attendances()->createMany([
            [
                'session_id' => Session::factory()->create(['course_id' => $this->course->id])->id,
                'status' => 'present',
            ],
            [
                'session_id' => Session::factory()->create(['course_id' => $this->course->id])->id,
                'status' => 'late',
            ],
        ]);

        $formatted = $this->student->formatted_global_attendance_rate;

        $this->assertEquals('75.00%', $formatted);
    }

    public function test_has_absence_alert_true(): void
    {
        // Créer un taux d'absence supérieur au seuil
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $i => $session) {
            $status = $i < 6 ? 'present' : 'absent'; // 40% d'absences
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $status,
            ]);
        }

        $this->assertTrue($this->student->hasAbsenceAlert());
    }

    public function test_has_absence_alert_false(): void
    {
        // Créer un bon taux de présence
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $session) {
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => 'present',
            ]);
        }

        $this->assertFalse($this->student->hasAbsenceAlert());
    }

    public function test_absence_alert_level_critical(): void
    {
        // Créer un taux d'absence critique
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $i => $session) {
            $status = $i < 6 ? 'present' : 'absent'; // 40% d'absences
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $status,
            ]);
        }

        $this->assertEquals('critical', $this->student->absence_alert_level);
    }

    public function test_absence_alert_level_warning(): void
    {
        // Créer un taux d'absence en alerte
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $i => $session) {
            $status = $i < 8 ? 'present' : 'absent'; // 20% d'absences
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $status,
            ]);
        }

        $this->assertEquals('warning', $this->student->absence_alert_level);
    }

    public function test_absence_alert_level_none(): void
    {
        // Créer un bon taux de présence
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $session) {
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => 'present',
            ]);
        }

        $this->assertEquals('none', $this->student->absence_alert_level);
    }

    public function test_get_attendance_rate_for_course(): void
    {
        // Créer un autre cours pour tester
        $otherCourse = Course::factory()->create();
        $this->student->courses()->attach($otherCourse->id);

        // Ajouter des présences pour le premier cours
        $sessions1 = Session::factory()->count(4)->create(['course_id' => $this->course->id]);
        foreach ($sessions1 as $i => $session) {
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $i < 2 ? 'present' : 'absent',
            ]);
        }

        // Ajouter des présences pour l'autre cours
        $sessions2 = Session::factory()->count(4)->create(['course_id' => $otherCourse->id]);
        foreach ($sessions2 as $session) {
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => 'present',
            ]);
        }

        $rate1 = $this->student->getAttendanceRateForCourse($this->course);
        $rate2 = $this->student->getAttendanceRateForCourse($otherCourse);

        // Premier cours : 50% de présence
        $this->assertEquals(50.0, $rate1);
        
        // Deuxième cours : 100% de présence
        $this->assertEquals(100.0, $rate2);
    }

    public function test_attendance_scopes(): void
    {
        // Créer différents types d'utilisateurs
        $teacher = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Ajouter des présences pour l'étudiant
        $session = Session::factory()->create(['course_id' => $this->course->id]);
        Attendance::factory()->create([
            'session_id' => $session->id,
            'user_id' => $this->student->id,
            'status' => 'present',
        ]);

        // Tester les scopes
        $students = User::students()->get();
        $teachers = User::teachers()->get();
        $admins = User::admins()->get();

        $this->assertTrue($students->contains($this->student));
        $this->assertFalse($students->contains($teacher));
        $this->assertFalse($students->contains($admin));

        $this->assertFalse($teachers->contains($this->student));
        $this->assertTrue($teachers->contains($teacher));
        $this->assertFalse($teachers->contains($admin));

        $this->assertFalse($admins->contains($this->student));
        $this->assertFalse($admins->contains($teacher));
        $this->assertTrue($admins->contains($admin));
    }

    public function test_role_helper_methods(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isTeacher());
        $this->assertFalse($student->isAdmin());

        $this->assertFalse($teacher->isStudent());
        $this->assertTrue($teacher->isTeacher());
        $this->assertFalse($teacher->isAdmin());

        $this->assertFalse($admin->isStudent());
        $this->assertFalse($admin->isTeacher());
        $this->assertTrue($admin->isAdmin());
    }

    public function test_no_attendances_returns_100_percent(): void
    {
        // Sans présences, le taux doit être de 100%
        $rate = $this->student->global_attendance_rate;
        $this->assertEquals(100.0, $rate);

        $courseRate = $this->student->getAttendanceRateForCourse($this->course);
        $this->assertEquals(100.0, $courseRate);
    }
}
