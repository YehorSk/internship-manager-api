<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    protected $table = 'documents';

    protected $primaryKey = 'id';

    protected $fillable = [
        'practice_id',
        'type',
        'file_path',
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
