<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'round_number',
        'season_round_number',
        'date',
        'status',
        'registration_deadline',
    ];

    protected function casts(): array
    {
        return [
            'date'                  => 'date',
            'registration_deadline' => 'datetime',
            'round_number'          => 'integer',
            'season_round_number'   => 'integer',
        ];
    }

    // Relations

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function pairings(): HasMany
    {
        return $this->hasMany(Pairing::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function roundPlayerStatuses(): HasMany
    {
        return $this->hasMany(RoundPlayerStatus::class);
    }
}
