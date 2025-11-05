<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $fillable = [
        'first_name',
        'last_name',
        'student_email',
        'primary_email',
        'phone',
        'address',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function studyPrograms()
    {
        return $this->belongsToMany(StudyProgram::class, 'student_study_program', 'student_id', 'study_program_id');
    }

    public function practices()
    {
        return $this->hasMany(Practice::class, 'student_id', 'user_id');
    }
}
