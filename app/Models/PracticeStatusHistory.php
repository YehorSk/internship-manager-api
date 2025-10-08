<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeStatusHistory extends Model
{
    /** @use HasFactory<\Database\Factories\PracticeStatusHistoryFactory> */
    use HasFactory;

    protected $table = 'practice_status_history';

    protected $primaryKey = 'id';

    protected $fillable = [
        'practice_id',
        'user_id',
        'document_id',
        'status',
        'comment',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function practice()
    {
        return $this->belongsTo(Practice::class, 'practice_id', 'id');
    }
}
