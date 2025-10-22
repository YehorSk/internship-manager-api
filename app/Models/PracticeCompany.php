<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeCompany extends Model
{
    /** @use HasFactory<\Database\Factories\PracticeCompanyFactory> */
    use HasFactory;

    protected $table = 'practice_companies';

    protected $primaryKey = 'id';

    protected $fillable = [
        'practice_id',
        'name',
        'address',
        'ico',
        'contact_phone',
        'contact_email',
        'contact_name',
        'company_email',
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
