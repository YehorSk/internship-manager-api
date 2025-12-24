<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyProgram extends Model
{
    protected $table = 'study_programs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_study_program', 'study_program_id', 'student_id')->withTimestamps();
    }
}
