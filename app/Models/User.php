<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'is_active',
        'auto_participate',
        'joined_at_round_id',
        'elo_rating',
        'knsb_relatienummer',
        'show_knsb_rating',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'email',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_participate' => 'boolean',
            'show_knsb_rating' => 'boolean',
        ];
    }

    // Relations

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function pairingsAsWhite(): HasMany
    {
        return $this->hasMany(Pairing::class, 'white_user_id');
    }

    public function pairingsAsBlack(): HasMany
    {
        return $this->hasMany(Pairing::class, 'black_user_id');
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function roundPlayerStatuses(): HasMany
    {
        return $this->hasMany(RoundPlayerStatus::class);
    }

    public function magicLinks(): HasMany
    {
        return $this->hasMany(MagicLink::class);
    }

    public function joinedAtRound(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'joined_at_round_id');
    }

    public function eloRatings(): HasMany
    {
        return $this->hasMany(EloRating::class);
    }
}
