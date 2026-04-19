<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['course_session_id', 'user_id', 'status', 'note'];

    public function session()
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
