<?php

namespace App\Models;

use App\Enums\PracticeStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Practice extends Model
{
    /** @use HasFactory<\Database\Factories\PracticeFactory> */
    use HasFactory;

    protected $table = 'practices';

    protected $primaryKey = 'id';

    protected $fillable = [
        'student_id',
        'company_id',
        'semester',
        'academic_year',
        'start_date',
        'end_date',
        'status',
        'study_program_id',
        'job_title',
        'job_description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'user_id');
    }

    public function practiceCompany()
    {
        return $this->hasOne(PracticeCompany::class, 'practice_id');
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id', 'id');
    }

    public function practiceStatusHistory()
    {
        return $this->hasMany(PracticeStatusHistory::class, 'practice_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'practice_id');
    }

    public function hasDocumentType(string $type): bool{
        return $this->documents()->where('type', $type)->exists();
    }

    public function lastStatusIs(PracticeStatusEnum $status): bool
    {
        $lastStatus = $this->practiceStatusHistory->last()->status;
        return $lastStatus === $status->value;
    }

}
