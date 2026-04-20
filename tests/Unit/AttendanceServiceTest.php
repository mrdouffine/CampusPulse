<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Session;
use App\Models\Attendance;
use App\Models\Setting;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;
    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceService();
        
        // Configuration initiale
        Setting::set('absence_threshold', 30);
        Setting::set('late_weight', 0.5);

        // Créer un étudiant et un cours
        $this->student = User::factory()->create(['role' => 'student']);
        $this->course = Course::factory()->create();
        
        // Inscrire l'étudiant au cours
        $this->course->users()->attach($this->student->id);
    }

    public function test_calculate_attendance_rate_all_present(): void
    {
        // Créer des présences
        $session = Session::factory()->create(['course_id' => $this->course->id]);
        Attendance::factory()->create([
            'session_id' => $session->id,
            'user_id' => $this->student->id,
            'status' => 'present',
        ]);

        $rate = $this->service->calculateAttendanceRate($this->student, $this->course);

        $this->assertEquals(100.0, $rate);
    }

    public function test_calculate_attendance_rate_all_absent(): void
    {
        // Créer des absences
        $session = Session::factory()->create(['course_id' => $this->course->id]);
        Attendance::factory()->create([
            'session_id' => $session->id,
            'user_id' => $this->student->id,
            'status' => 'absent',
        ]);

        $rate = $this->service->calculateAttendanceRate($this->student, $this->course);

        $this->assertEquals(0.0, $rate);
    }

    public function test_calculate_attendance_rate_with_late(): void
    {
        // Créer un mélange de présences et retards
        $session1 = Session::factory()->create(['course_id' => $this->course->id]);
        $session2 = Session::factory()->create(['course_id' => $this->course->id]);

        Attendance::factory()->create([
            'session_id' => $session1->id,
            'user_id' => $this->student->id,
            'status' => 'present',
        ]);

        Attendance::factory()->create([
            'session_id' => $session2->id,
            'user_id' => $this->student->id,
            'status' => 'late',
        ]);

        $rate = $this->service->calculateAttendanceRate($this->student, $this->course);

        // (1 présent + 0.5 retard) / 2 = 75%
        $this->assertEquals(75.0, $rate);
    }

    public function test_calculate_attendance_rate_no_attendances(): void
    {
        $rate = $this->service->calculateAttendanceRate($this->student, $this->course);

        $this->assertEquals(100.0, $rate);
    }

    public function test_get_students_at_risk_critical(): void
    {
        // Créer un étudiant avec un taux d'absence critique
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $i => $session) {
            $status = $i < 4 ? 'present' : 'absent'; // 60% d'absences
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $status,
            ]);
        }

        $atRiskStudents = $this->service->getStudentsAtRisk($this->course);

        $this->assertCount(1, $atRiskStudents);
        $this->assertEquals('critical', $atRiskStudents->first()['risk_level']);
        $this->assertEquals(40.0, $atRiskStudents->first()['attendance_rate']);
    }

    public function test_get_students_at_risk_warning(): void
    {
        // Créer un étudiant avec un taux d'absence en alerte
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $i => $session) {
            $status = $i < 8 ? 'present' : 'absent'; // 20% d'absences
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $status,
            ]);
        }

        $atRiskStudents = $this->service->getStudentsAtRisk($this->course);

        $this->assertCount(1, $atRiskStudents);
        $this->assertEquals('warning', $atRiskStudents->first()['risk_level']);
        $this->assertEquals(80.0, $atRiskStudents->first()['attendance_rate']);
    }

    public function test_get_students_at_risk_no_risk(): void
    {
        // Créer un étudiant avec un bon taux de présence
        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $session) {
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => 'present',
            ]);
        }

        $atRiskStudents = $this->service->getStudentsAtRisk($this->course);

        $this->assertCount(0, $atRiskStudents);
    }

    public function test_generate_attendance_report(): void
    {
        // Créer plusieurs étudiants et sessions
        $students = User::factory()->count(3)->create(['role' => 'student']);
        foreach ($students as $student) {
            $this->course->users()->attach($student->id);
        }

        $sessions = Session::factory()->count(5)->create(['course_id' => $this->course->id]);
        
        // Créer des présences variées
        foreach ($sessions as $session) {
            foreach ($students as $i => $student) {
                $status = match($i % 3) {
                    0 => 'present',
                    1 => 'late',
                    2 => 'absent',
                };
                Attendance::factory()->create([
                    'session_id' => $session->id,
                    'user_id' => $student->id,
                    'status' => $status,
                ]);
            }
        }

        $report = $this->service->generateAttendanceReport($this->course);

        // Vérifier la structure du rapport
        $this->assertArrayHasKey('course', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('students', $report);
        $this->assertArrayHasKey('sessions', $report);

        // Vérifier les statistiques
        $this->assertEquals(3, $report['summary']['total_students']);
        $this->assertEquals(5, $report['summary']['total_sessions']);
        $this->assertCount(3, $report['students']);
        $this->assertCount(5, $report['sessions']);
    }

    public function test_batch_create_attendances(): void
    {
        $session = Session::factory()->create(['course_id' => $this->course->id]);
        $students = User::factory()->count(3)->create(['role' => 'student']);
        
        foreach ($students as $student) {
            $this->course->users()->attach($student->id);
        }

        $attendanceData = [];
        foreach ($students as $i => $student) {
            $attendanceData[] = [
                'session_id' => $session->id,
                'user_id' => $student->id,
                'status' => $i % 2 === 0 ? 'present' : 'late',
                'note' => 'Test note ' . $i,
            ];
        }

        $this->service->batchCreateAttendances($attendanceData);

        $this->assertEquals(3, Attendance::count());
        
        foreach ($attendanceData as $data) {
            $this->assertDatabaseHas('attendances', [
                'session_id' => $data['session_id'],
                'user_id' => $data['user_id'],
                'status' => $data['status'],
                'note' => $data['note'],
            ]);
        }
    }

    public function test_batch_update_attendances(): void
    {
        $session = Session::factory()->create(['course_id' => $this->course->id]);
        
        // Créer des présences initiales
        Attendance::factory()->create([
            'session_id' => $session->id,
            'user_id' => $this->student->id,
            'status' => 'present',
        ]);

        $attendanceData = [
            [
                'user_id' => $this->student->id,
                'status' => 'late',
                'note' => 'Updated note',
            ],
        ];

        $this->service->batchUpdateAttendances($session->id, $attendanceData);

        $attendance = Attendance::where('session_id', $session->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertEquals('late', $attendance->status);
        $this->assertEquals('Updated note', $attendance->note);
    }

    public function test_custom_late_weight(): void
    {
        // Modifier le poids des retards
        Setting::set('late_weight', 0.25);

        $session1 = Session::factory()->create(['course_id' => $this->course->id]);
        $session2 = Session::factory()->create(['course_id' => $this->course->id]);

        Attendance::factory()->create([
            'session_id' => $session1->id,
            'user_id' => $this->student->id,
            'status' => 'present',
        ]);

        Attendance::factory()->create([
            'session_id' => $session2->id,
            'user_id' => $this->student->id,
            'status' => 'late',
        ]);

        $rate = $this->service->calculateAttendanceRate($this->student, $this->course);

        // (1 présent + 0.25 retard) / 2 = 62.5%
        $this->assertEquals(62.5, $rate);
    }

    public function test_custom_absence_threshold(): void
    {
        // Modifier le seuil d'absence
        Setting::set('absence_threshold', 20);

        $sessions = Session::factory()->count(10)->create(['course_id' => $this->course->id]);
        
        foreach ($sessions as $i => $session) {
            $status = $i < 8 ? 'present' : 'absent'; // 20% d'absences
            Attendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->student->id,
                'status' => $status,
            ]);
        }

        $atRiskStudents = $this->service->getStudentsAtRisk($this->course);

        // Avec un seuil de 20%, 20% d'absences = critique
        $this->assertCount(1, $atRiskStudents);
        $this->assertEquals('critical', $atRiskStudents->first()['risk_level']);
    }
}
