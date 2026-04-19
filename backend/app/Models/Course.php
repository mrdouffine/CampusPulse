<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['name', 'code', 'teacher_user_id', 'semester', 'total_hours'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_user')->withPivot('enrolled_at');
    }

    public function sessions()
    {
        return $this->hasMany(CourseSession::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}
