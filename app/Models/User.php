<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'student_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => 'string',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function presentAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->where('status', 'present');
    }

    public function absentAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->where('status', 'absent');
    }

    public function lateAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->where('status', 'late');
    }

    public function getAttendanceRateForCourse(Course $course): float
    {
        $attendances = $this->attendances()
            ->whereHas('session', function (Builder $query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->get();

        if ($attendances->isEmpty()) {
            return 100.0;
        }

        $lateWeight = Setting::get('late_weight', 0.5);
        $totalWeight = 0;
        $presentWeight = 0;

        foreach ($attendances as $attendance) {
            $weight = $attendance->weight;
            $totalWeight += 1.0;
            $presentWeight += $weight;
        }

        return round(($presentWeight / $totalWeight) * 100, 2);
    }

    public function getGlobalAttendanceRateAttribute(): float
    {
        $attendances = $this->attendances;
        
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

    public function getFormattedGlobalAttendanceRateAttribute(): string
    {
        return number_format($this->global_attendance_rate, 2) . '%';
    }

    public function hasAbsenceAlert(): bool
    {
        $threshold = Setting::get('absence_threshold', 30);
        $absenceRate = 100 - $this->global_attendance_rate;
        
        return $absenceRate >= $threshold;
    }

    public function getAbsenceAlertLevelAttribute(): string
    {
        if (!$this->hasAbsenceAlert()) {
            return 'none';
        }

        $threshold = Setting::get('absence_threshold', 30);
        $absenceRate = 100 - $this->global_attendance_rate;
        
        if ($absenceRate >= $threshold) {
            return 'critical';
        }
        
        $warningThreshold = $threshold - 10;
        if ($absenceRate >= $warningThreshold) {
            return 'warning';
        }
        
        return 'notice';
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', 'student');
    }

    public function scopeTeachers(Builder $query): Builder
    {
        return $query->where('role', 'teacher');
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    public function scopeAtRisk(Builder $query): Builder
    {
        $threshold = Setting::get('absence_threshold', 30);
        
        return $query->students()->whereHas('attendances', function (Builder $query) use ($threshold) {
            // Calcul du taux d'absence via une sous-requête
            $query->selectRaw('COUNT(*)')
                  ->whereIn('status', ['absent', 'late'])
                  ->havingRaw('(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendances a2 WHERE a2.user_id = attendances.user_id)) >= ?', [$threshold]);
        });
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
