<?php

namespace App\Models;

use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function supervisor()
    {
        return $this->hasOne(Supervisor::class, 'user_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function hasRole($role)
    {
        // Явно префиксуем имя колонки таблицей roles, чтобы избежать неоднозначности
        return $this->roles()->where('roles.name', $role)->exists();
    }

    public function hasRoleId(int $roleId): bool
    {
        // Явно префиксуем имя колонки таблицей roles, иначе при join с pivot-таблицей
        // может возникать "Column 'id' in WHERE is ambiguous" (если в pivot тоже есть id)
        return $this->roles()->where('roles.id', $roleId)->exists();
    }

    public function markEmailAsVerified()
    {
        $updated = $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();

        if ($updated && $this->role_id === 3) {
            // Ваша логика для компаний (например, уведомление, смена статуса и т.д.)
            $this->company->update(['status' => 'approved']);
        }

        return $updated;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }
}
