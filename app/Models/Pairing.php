<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pairing extends Model
{
    use HasFactory;

    protected $fillable = [
        'round_id',
        'board_number',
        'white_user_id',
        'black_user_id',
        'result',
        'is_bye',
        'bye_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_bye' => 'boolean',
        ];
    }

    // Relations

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function whitePlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'white_user_id');
    }

    public function blackPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'black_user_id');
    }

    public function byePlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bye_user_id');
    }
}
