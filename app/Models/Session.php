<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'date',
        'topic',
        'duration_minutes',
    ];

    protected $casts = [
        'date' => 'date',
        'duration_minutes' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
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

    public function getAttendanceRateAttribute(): float
    {
        $totalAttendances = $this->attendances()->count();
        if ($totalAttendances === 0) {
            return 100.0;
        }

        $presentCount = $this->presentAttendances()->count();
        $lateCount = $this->lateAttendances()->count();
        
        // Le retard compte comme demi-présence (configurable)
        $lateWeight = config('attendance.late_weight', 0.5);
        
        $effectivePresent = $presentCount + ($lateCount * $lateWeight);
        
        return round(($effectivePresent / $totalAttendances) * 100, 2);
    }
}
