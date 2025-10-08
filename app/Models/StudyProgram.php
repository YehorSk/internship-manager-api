<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyProgram extends Model
{
    use HasFactory;

    protected $table = 'study_programs';

    protected $fillable = [
        'code',
        'name',
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_study_program', 'study_program_id', 'student_id');
    }
}
