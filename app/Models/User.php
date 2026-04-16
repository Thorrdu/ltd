<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'prospect'  => ['label' => 'Prospect',  'level' => 1],
        'member'    => ['label' => 'Membre',     'level' => 2],
        'officer'   => ['label' => 'Officier',   'level' => 3],
        'treasurer' => ['label' => 'Trésorier',  'level' => 4],
        'president' => ['label' => 'Président',  'level' => 5],
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'sim_pin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'sim_pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Role helpers ─────────────────────────────────────────

    public function getRoleLevel(): int
    {
        return self::ROLES[$this->role]['level'] ?? 0;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role]['label'] ?? $this->role;
    }

    public function isAtLeast(string $role): bool
    {
        $required = self::ROLES[$role]['level'] ?? 0;

        return $this->getRoleLevel() >= $required;
    }

    public function isProspect(): bool
    {
        return $this->role === 'prospect';
    }

    public function isMember(): bool
    {
        return $this->isAtLeast('member');
    }

    public function isOfficer(): bool
    {
        return $this->isAtLeast('officer');
    }

    public function isTreasurer(): bool
    {
        return $this->isAtLeast('treasurer');
    }

    public function isPresident(): bool
    {
        return $this->role === 'president';
    }

    public static function roleOptions(): array
    {
        return collect(self::ROLES)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->all();
    }

    // ── Filament access ──────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin'     => $this->isAtLeast('treasurer'),
            'armurerie' => $this->isAtLeast('officer'),
            default     => false,
        };
    }

    // ── Sim PIN ──────────────────────────────────────────────

    public function checkSimPin(string $pin): bool
    {
        if (! $this->sim_pin) {
            return false;
        }

        return Hash::check($pin, $this->sim_pin);
    }
}
