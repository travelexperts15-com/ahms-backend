<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotification extends Model
{
    use HasFactory;

    protected $table = 'system_notifications';

    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'action_url', 'is_read', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Send a notification to a specific user.
     */
    public static function send(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): self
    {
        return static::create([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Broadcast to all users with a given role.
     */
    public static function sendToRole(string $role, string $title, string $message, string $type = 'info'): void
    {
        User::role($role)->pluck('id')->each(function ($userId) use ($title, $message, $type) {
            static::send($userId, $title, $message, $type);
        });
    }
}
