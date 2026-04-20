<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Attendance;
use App\Models\Setting;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function calculateAttendanceRate(User $user, ?Course $course = null): float
    {
        $query = $user->attendances();

        if ($course) {
            $query->whereHas('session', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            });
        }

        $attendances = $query->get();

        if ($attendances->isEmpty()) {
            return 100.0;
        }

        $lateWeight = Setting::get('late_weight', 0.5);
        $totalWeight = 0;
        $presentWeight = 0;

        foreach ($attendances as $attendance) {
            $totalWeight += 1.0;
            $presentWeight += $attendance->weight;
        }

        return round(($presentWeight / $totalWeight) * 100, 2);
    }

    public function getStudentsAtRisk(Course $course): Collection
    {
        $threshold = Setting::get('absence_threshold', 30);
        $warningThreshold = max(0, $threshold - 10);

        return $course->users()
            ->students()
            ->get()
            ->map(function ($student) use ($threshold, $warningThreshold) {
                $attendanceRate = $this->calculateAttendanceRate($student, $course);
                $absenceRate = 100 - $attendanceRate;

                return [
                    'student' => $student,
                    'attendance_rate' => $attendanceRate,
                    'absence_rate' => $absenceRate,
                    'risk_level' => $this->getRiskLevel($absenceRate, $threshold, $warningThreshold),
                ];
            })
            ->filter(function ($data) {
                return $data['risk_level'] !== 'none';
            })
            ->sortByDesc('absence_rate')
            ->values();
    }

    private function getRiskLevel(float $absenceRate, float $threshold, float $warningThreshold): string
    {
        if ($absenceRate >= $threshold) {
            return 'critical';
        }

        if ($absenceRate >= $warningThreshold) {
            return 'warning';
        }

        return 'none';
    }

    public function generateAttendanceReport(Course $course): array
    {
        $students = $course->users()->students()->get();
        $sessions = $course->sessions()->with('attendances')->get();

        $report = [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
            ],
            'summary' => [
                'total_students' => $students->count(),
                'total_sessions' => $sessions->count(),
                'global_attendance_rate' => $this->calculateGlobalCourseAttendance($course),
                'students_at_risk' => $this->getStudentsAtRisk($course)->count(),
            ],
            'students' => [],
            'sessions' => [],
        ];

        // Détails par étudiant
        foreach ($students as $student) {
            $attendanceRate = $this->calculateAttendanceRate($student, $course);
            $absenceRate = 100 - $attendanceRate;
            
            $report['students'][] = [
                'id' => $student->id,
                'name' => $student->name,
                'student_number' => $student->student_number,
                'attendance_rate' => $attendanceRate,
                'absence_rate' => $absenceRate,
                'present_count' => $student->attendances()->whereHas('session', function ($q) use ($course) {
                    $q->where('course_id', $course->id);
                })->where('status', 'present')->count(),
                'absent_count' => $student->attendances()->whereHas('session', function ($q) use ($course) {
                    $q->where('course_id', $course->id);
                })->where('status', 'absent')->count(),
                'late_count' => $student->attendances()->whereHas('session', function ($q) use ($course) {
                    $q->where('course_id', $course->id);
                })->where('status', 'late')->count(),
                'at_risk' => $absenceRate >= Setting::get('absence_threshold', 30),
            ];
        }

        // Détails par session
        foreach ($sessions as $session) {
            $report['sessions'][] = [
                'id' => $session->id,
                'date' => $session->date->format('Y-m-d'),
                'topic' => $session->topic,
                'attendance_rate' => $session->attendance_rate,
                'present_count' => $session->presentAttendances()->count(),
                'absent_count' => $session->absentAttendances()->count(),
                'late_count' => $session->lateAttendances()->count(),
                'total_students' => $session->attendances()->count(),
            ];
        }

        return $report;
    }

    private function calculateGlobalCourseAttendance(Course $course): float
    {
        $totalAttendances = Attendance::whereHas('session', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->count();

        if ($totalAttendances === 0) {
            return 100.0;
        }

        $presentAttendances = Attendance::whereHas('session', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->whereIn('status', ['present', 'late'])->get();

        $lateWeight = Setting::get('late_weight', 0.5);
        $totalWeight = 0;
        $presentWeight = 0;

        foreach ($presentAttendances as $attendance) {
            $totalWeight += 1.0;
            $presentWeight += $attendance->weight;
        }

        $absentCount = $totalAttendances - $presentAttendances->count();
        $totalWeight += $absentCount;

        return round(($presentWeight / $totalWeight) * 100, 2);
    }

    public function batchCreateAttendances(array $attendanceData): void
    {
        $attendances = [];
        
        foreach ($attendanceData as $data) {
            $attendances[] = [
                'session_id' => $data['session_id'],
                'user_id' => $data['user_id'],
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Attendance::insert($attendances);
    }

    public function batchUpdateAttendances(int $sessionId, array $attendanceData): void
    {
        foreach ($attendanceData as $data) {
            Attendance::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'user_id' => $data['user_id'],
                ],
                [
                    'status' => $data['status'],
                    'note' => $data['note'] ?? null,
                ]
            );
        }
    }
}
