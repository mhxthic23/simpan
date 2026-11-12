<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderPreferences extends Model
{
    protected $table = 'reminder_preferences';

    protected $fillable = [
        'user_id', 'enabled', 'channel', 'reminder_before_minutes', 'quiet_hours_start', 'quiet_hours_end',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'reminder_before_minutes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
