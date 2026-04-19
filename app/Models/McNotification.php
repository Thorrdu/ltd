<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'link',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Create a notification for a specific user.
     */
    public static function notify(int $userId, string $type, string $title, ?string $body = null, ?string $link = null): self
    {
        return static::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'link'    => $link,
        ]);
    }

    /**
     * Broadcast a notification to all users with at least a given role.
     */
    public static function broadcast(string $minRole, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        $minLevel = User::ROLES[$minRole]['level'] ?? 0;
        $users = User::where('is_active', true)->get();

        foreach ($users as $user) {
            if ($user->getRoleLevel() >= $minLevel) {
                static::create([
                    'user_id' => $user->id,
                    'type'    => $type,
                    'title'   => $title,
                    'body'    => $body,
                    'link'    => $link,
                ]);
            }
        }
    }
}
