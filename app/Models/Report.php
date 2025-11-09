<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    protected $table = 'reports';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'report_type',
        'params',
        'file_path',
        'started_at',
        'ended_at',
        'status',
        'message',
        'task_id',
    ];

    protected $hidden = [
         'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'user_id', 'user_id');
    }
}
