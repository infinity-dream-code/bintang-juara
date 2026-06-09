<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class CyberKey extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = "cyber_key";

    protected $primaryKey = "urut";

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        "users",
        "kunci",
        "fid",
        "ket",
        "kel",
        "urut",
        "password",
    ];

    protected $hidden = ["password"];

    public function getAuthIdentifierName(): string
    {
        return "urut";
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    public function getIdAttribute(): int
    {
        return (int) $this->urut;
    }

    public function getNameAttribute(): string
    {
        return $this->ket ?: (string) $this->users;
    }

    public function hasRole(string $role): bool
    {
        if ($role === "siswa") {
            return false;
        }

        if (in_array($role, ["admin", "super-admin"], true)) {
            return true;
        }

        return $this->kel === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }
}
