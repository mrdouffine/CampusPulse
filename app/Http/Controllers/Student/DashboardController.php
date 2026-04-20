<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Attendance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:student');
    }

    public function index(): Response
    {
        $user = Auth::user();
        
        // Récupérer les cours de l'étudiant
        $courses = $user->courses()
            ->with(['sessions.attendances' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // Calculer les statistiques globales
        $globalStats = $this->calculateGlobalStats($user);
        
        // Calculer les statistiques par cours
        $courseStats = $this->calculateCourseStats($courses, $user);
        
        // Récupérer les prochaines séances
        $upcomingSessions = $this->getUpcomingSessions($courses);
        
        // Vérifier les alertes
        $alertData = $this->getAlertData($user);

        return Inertia::render('Student/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'student_number' => $user->student_number,
            ],
            'globalStats' => $globalStats,
            'courseStats' => $courseStats,
            'upcomingSessions' => $upcomingSessions,
            'alert' => $alertData,
            'absenceThreshold' => Setting::get('absence_threshold', 30),
        ]);
    }

    private function calculateGlobalStats(User $user): array
    {
        $attendances = $user->attendances;
        
        if ($attendances->isEmpty()) {
            return [
                'total_sessions' => 0,
                'attendance_rate' => 100.0,
                'present_count' => 0,
                'absent_count' => 0,
                'late_count' => 0,
                'formatted_attendance_rate' => '100.00%',
            ];
        }

        $presentCount = $attendances->where('status', 'present')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $totalSessions = $attendances->count();

        // Calcul du taux avec poids pour les retards
        $lateWeight = Setting::get('late_weight', 0.5);
        $effectivePresent = $presentCount + ($lateCount * $lateWeight);
        $attendanceRate = round(($effectivePresent / $totalSessions) * 100, 2);

        return [
            'total_sessions' => $totalSessions,
            'attendance_rate' => $attendanceRate,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'formatted_attendance_rate' => number_format($attendanceRate, 2) . '%',
        ];
    }

    private function calculateCourseStats($courses, User $user): array
    {
        return $courses->map(function ($course) use ($user) {
            $courseAttendances = $course->sessions()
                ->whereHas('attendances', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['attendances' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])
                ->get()
                ->flatMap->attendances;

            if ($courseAttendances->isEmpty()) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'code' => $course->code,
                    'attendance_rate' => 100.0,
                    'present_count' => 0,
                    'absent_count' => 0,
                    'late_count' => 0,
                    'total_sessions' => 0,
                    'has_alert' => false,
                    'formatted_attendance_rate' => '100.00%',
                ];
            }

            $presentCount = $courseAttendances->where('status', 'present')->count();
            $absentCount = $courseAttendances->where('status', 'absent')->count();
            $lateCount = $courseAttendances->where('status', 'late')->count();
            $totalSessions = $courseAttendances->count();

            $lateWeight = Setting::get('late_weight', 0.5);
            $effectivePresent = $presentCount + ($lateCount * $lateWeight);
            $attendanceRate = round(($effectivePresent / $totalSessions) * 100, 2);

            $threshold = Setting::get('absence_threshold', 30);
            $absenceRate = 100 - $attendanceRate;
            $hasAlert = $absenceRate >= $threshold;

            return [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
                'attendance_rate' => $attendanceRate,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'total_sessions' => $totalSessions,
                'has_alert' => $hasAlert,
                'formatted_attendance_rate' => number_format($attendanceRate, 2) . '%',
            ];
        })->toArray();
    }

    private function getUpcomingSessions($courses): array
    {
        $upcomingSessions = $courses
            ->flatMap(function ($course) {
                return $course->sessions()
                    ->where('date', '>=', now())
                    ->orderBy('date', 'asc')
                    ->limit(3)
                    ->get();
            })
            ->sortBy('date')
            ->take(5)
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'course_name' => $session->course->name,
                    'course_code' => $session->course->code,
                    'date' => $session->date->format('Y-m-d'),
                    'formatted_date' => $session->date->locale('fr')->translatedFormat('d F Y'),
                    'topic' => $session->topic,
                    'duration_minutes' => $session->duration_minutes,
                    'days_until' => now()->diffInDays($session->date, false),
                ];
            })
            ->toArray();

        return $upcomingSessions;
    }

    private function getAlertData(User $user): ?array
    {
        $attendanceRate = $user->global_attendance_rate;
        $absenceRate = 100 - $attendanceRate;
        $threshold = Setting::get('absence_threshold', 30);

        if ($absenceRate < $threshold - 10) {
            return null; // Pas d'alerte
        }

        $attendances = $user->attendances;
        $presentCount = $attendances->where('status', 'present')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();

        $alertLevel = 'notice';
        if ($absenceRate >= $threshold) {
            $alertLevel = 'critical';
        } elseif ($absenceRate >= $threshold - 10) {
            $alertLevel = 'warning';
        }

        return [
            'level' => $alertLevel,
            'attendance_rate' => $attendanceRate,
            'absence_rate' => $absenceRate,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'threshold' => $threshold,
            'message' => $this->getAlertMessage($alertLevel, $absenceRate, $threshold),
        ];
    }

    private function getAlertMessage(string $level, float $absenceRate, float $threshold): string
    {
        switch ($level) {
            case 'critical':
                return "Votre taux d'absence est de {$absenceRate}%, ce qui dépasse le seuil critique de {$threshold}%. Votre participation au cours est compromise.";
            case 'warning':
                return "Votre taux d'absence est de {$absenceRate}%, ce qui approche le seuil d'alerte de {$threshold}%. Veillez à améliorer votre assiduité.";
            default:
                return "Votre taux d'absence est de {$absenceRate}%. Continuez vos efforts pour maintenir une bonne présence.";
        }
    }
}
