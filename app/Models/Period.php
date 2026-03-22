<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'number',
        'pairing_system',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }

    // Relations

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }
}
