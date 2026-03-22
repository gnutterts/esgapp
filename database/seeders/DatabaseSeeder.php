<?php

namespace Database\Seeders;

use App\Models\Period;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create wedstrijdleider user
        $admin = User::firstOrCreate(
            ['email' => 'goferhout@gmail.com'],
            [
                'name' => 'Gert Nutterts',
                'is_active' => true,
                'auto_participate' => false,
            ]
        );
        $admin->role = 'wedstrijdleider';
        $admin->save();

        // Create default settings
        $settings = [
            'max_waardering'      => '60',
            'punten_extern'       => '40',
            'punten_afwezig'      => '20',
            'punten_oneven'       => '40',
            'max_afwezig'         => '5',
            'punten_nieuwe_speler'=> '15',
            'factor_winst'        => '1',
            'factor_remise'       => '0.5',
            'factor_verlies'      => '0',
            'min_deelname'        => '7',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['key' => $key, 'value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Create test season if no season exists yet
        if (Season::count() === 0) {
            $seizoen = Season::create([
                'name'       => 'Test seizoen',
                'start_date' => '2025-09-15',
                'end_date'   => '2026-06-29',
                'is_current' => true,
            ]);

            foreach ([1 => 'swiss', 2 => 'keizer', 3 => 'keizer', 4 => 'keizer'] as $number => $system) {
                Period::create([
                    'season_id'      => $seizoen->id,
                    'number'         => $number,
                    'pairing_system' => $system,
                ]);
            }
        }
    }
}
