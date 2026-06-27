<?php

namespace App\Models;

use App\Models\PageAccessRule;
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
        'prospect'       => ['label' => 'Prospect',        'level' => 1],
        'nomade'         => ['label' => 'Nomade',          'level' => 2],
        'member'         => ['label' => 'Membre',          'level' => 2],
        'officer'        => ['label' => 'Officier',        'level' => 3],
        'vice_president' => ['label' => 'Vice-Président',  'level' => 4],
        'president'      => ['label' => 'Président',       'level' => 5],
        'treasurer'      => ['label' => 'Trésorier',       'level' => 99],
    ];

    public const SUPERADMIN_ROLE = 'treasurer';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'sim_pin',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
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
            'is_active' => 'boolean',
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

    public function isVicePresident(): bool
    {
        return $this->isAtLeast('vice_president');
    }

    public function isPresident(): bool
    {
        return $this->role === 'president' || $this->isSuperadmin();
    }

    public function isTreasurer(): bool
    {
        return $this->role === 'treasurer';
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::SUPERADMIN_ROLE;
    }

    public static function roleOptions(): array
    {
        return collect(self::ROLES)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->all();
    }

    /**
     * Returns true if this user is allowed to assign the given role.
     * Rules:
     *  - superadmin (treasurer) can assign any role, including another treasurer ;
     *  - président peut assigner n'importe quel rôle, y compris trésorier ;
     *  - sinon, un utilisateur peut assigner tout rôle de niveau strictement inférieur au sien.
     */
    public function canAssignRole(string $role): bool
    {
        if (! isset(self::ROLES[$role])) {
            return false;
        }
        if ($this->isSuperadmin()) {
            return true;
        }
        if ($this->isPresident()) {
            return true;
        }
        $target = self::ROLES[$role]['level'];

        return $this->getRoleLevel() > $target;
    }

    /**
     * List of roles this user may assign, as [['key' => 'member', 'label' => 'Membre'], ...].
     */
    public function assignableRoles(): array
    {
        $out = [];
        foreach (self::ROLES as $key => $data) {
            if ($this->canAssignRole($key)) {
                $out[] = ['key' => $key, 'label' => $data['label']];
            }
        }

        return $out;
    }

    // ── Filament access ──────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return PageAccessRule::userCanAccess($this, 'panel_' . $panel->getId());
    }

    public function canAccessPage(string $pageKey): bool
    {
        return PageAccessRule::userCanAccess($this, $pageKey);
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
