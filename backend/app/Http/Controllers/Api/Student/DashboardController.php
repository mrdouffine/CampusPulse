<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalSessions = clone $user->coursesEnrolled()->withCount('sessions')->get()->sum('sessions_count');
        $absencesCount = clone $user->attendances()->where('status', 'absent')->count();
        $lateCount = clone $user->attendances()->where('status', 'late')->count();

        // Le retard est compté comme demi-absence dans le calcul (spécifié dans cahier des charges)
        $effectiveAbsences = $absencesCount + ($lateCount * 0.5);
        $attendanceRate = $totalSessions > 0 ? (1 - ($effectiveAbsences / $totalSessions)) * 100 : 100;

        $absenceThreshold = floatval(Setting::where('key', 'absence_threshold')->value('value') ?? 20);

        return response()->json([
            'attendance_rate' => round($attendanceRate, 2),
            'total_absences' => $effectiveAbsences,
            'total_sessions' => $totalSessions,
            'is_at_risk' => (100 - $attendanceRate) >= $absenceThreshold,
            'threshold' => $absenceThreshold,
        ]);
    }
}
