<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Standing extends Model
{
    use HasFactory;

    protected $fillable = [
        'round_id',
        'user_id',
        'position',
        'position_change',
        'points',
        'games_played',
        'color_balance',
        'wins',
        'draws',
        'losses',
        'external_count',
        'bye_count',
        'absence_count',
    ];

    protected function casts(): array
    {
        return [
            'points'         => 'decimal:1',
            'position'       => 'integer',
            'position_change' => 'integer',
            'games_played'   => 'integer',
            'color_balance'  => 'integer',
            'wins'           => 'integer',
            'draws'          => 'integer',
            'losses'         => 'integer',
            'external_count' => 'integer',
            'bye_count'      => 'integer',
            'absence_count'  => 'integer',
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
