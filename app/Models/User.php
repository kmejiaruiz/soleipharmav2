<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'email', 'name', 'password',
        'role', 'branch', 'is_locked', 'session_password',
        'first_name', 'second_name', 'last_name', 'second_surname',
    ];

    protected $hidden = ['password', 'remember_token', 'session_password'];

    protected $casts = [
        'is_locked'         => 'boolean',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        $fullName = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if (!empty($fullName)) {
            return ucwords(strtolower($fullName));
        }
        if (!empty($this->name)) {
            return ucwords(strtolower($this->name));
        }
        return $this->username ?? 'Administración';
    }

    protected static function booted(): void
    {
        static::saving(function ($user) {
            if ($user->role === 'superadmin') {
                $user->branch = null;
            }
        });
    }

    public function getBranchAttribute($value): ?string
    {
        return $this->role === 'superadmin' ? null : $value;
    }

    // ── Roles helpers ──────────────────────────────────────────────────────────
    public function isAdmin(): bool      { return in_array($this->role, ['admin', 'superadmin']); }
    public function isSuperAdmin(): bool { return $this->role === 'superadmin'; }
    public function isCajero(): bool     { return $this->role === 'cajero'; }

    public static function verifyAdminCredentials(string $username, string $password): ?self
    {
        $user = static::where('username', $username)->first();
        return ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password) && $user->isAdmin())
            ? $user
            : null;
    }

    public static function generateUsername(string $firstName, string $lastName): string
    {
        $clean = function (string $str) {
            $ascii = \Illuminate\Support\Str::ascii(trim($str));
            $words = preg_split('/\s+/', $ascii, -1, PREG_SPLIT_NO_EMPTY);
            $firstWord = $words[0] ?? '';
            return preg_replace('/[^a-z0-9]/', '', strtolower($firstWord));
        };

        $first = $clean($firstName);
        $last  = $clean($lastName);

        if (empty($first) || empty($last)) {
            return '';
        }

        $initial = substr($first, 0, 1);

        $isAvailable = function (string $u) {
            $email = $u . '@soleipharma.com';
            return !static::where(function ($q) use ($u, $email) {
                $q->where('username', $u)->orWhere('email', $email);
            })->exists();
        };

        // 1. Primera letra del nombre + apellido (ej: jperez)
        $c1 = $initial . $last;
        if ($isAvailable($c1)) {
            return $c1;
        }

        // 2. Si ya existe: nombre.apellido (ej: juan.perez)
        $c2 = $first . '.' . $last;
        if ($isAvailable($c2)) {
            return $c2;
        }

        // 3. Si ya existe: primera letra de nombre + apellido + numero (ej: jperez1, jperez2...)
        $i = 1;
        while (!$isAvailable($c1 . $i)) {
            $i++;
        }

        return $c1 . $i;
    }

    // ── Relaciones ──────────────────────────────────────────────────────────────
    public function orders()          { return $this->hasMany(Order::class); }
    public function inventoryLogs()   { return $this->hasMany(InventoryLog::class, 'admin_id'); }
    public function discardRequests() { return $this->hasMany(DiscardRequest::class, 'requested_by'); }
    public function cashSessions()    { return $this->hasMany(CashSession::class, 'opened_by'); }
}
