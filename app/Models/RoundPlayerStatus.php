<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundPlayerStatus extends Model
{
    use HasFactory;

    protected $table = 'round_player_statuses';

    protected $fillable = [
        'round_id',
        'user_id',
        'status',
        'is_external_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'is_external_confirmed' => 'boolean',
        ];
    }

    // Relations

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
