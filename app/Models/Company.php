<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'address',
        'contact_name',
        'contact_position',
        'company_email',
        'contact_email',
        'contact_phone',
        'ico',
        'user_id',
        'registered_by',
        'status',
        'activation_token',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function practices()
    {
        return $this->hasMany(Practice::class, 'company_id', 'user_id');
    }
}
