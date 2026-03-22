<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloRating extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'rating',
        'source',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
