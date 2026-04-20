<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'status',
        'note',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'Retard',
            default => 'Inconnu',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'present' => 'green',
            'absent' => 'red',
            'late' => 'orange',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'present' => '✓',
            'absent' => '✗',
            'late' => '⏱',
            default => '?',
        };
    }

    public function getWeightAttribute(): float
    {
        return match($this->status) {
            'present' => 1.0,
            'late' => config('attendance.late_weight', 0.5),
            'absent' => 0.0,
            default => 0.0,
        };
    }
}
