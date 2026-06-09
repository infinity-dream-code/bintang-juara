<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "username",
        "name",
        "email",
        "password",
        "email_verified_at",
        "last_login",
        "token",
        "login_token",
        "is_active",
        "unit"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function getSekolahAttribute()
    {
        return $this->attributes['unit'] ?? null;
    }

    public static function getTandaTanganBase64(): ?string
    {
        return null;
    }

    public static function getLogoSekolahBase64(): ?string
    {
        return null;
    }
}
