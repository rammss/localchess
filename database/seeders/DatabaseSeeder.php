<?php

namespace Database\Seeders;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Minimal local bootstrap only — creates an admin user with a real
     * (non-personal) team so there's something to log into and manage
     * before running `import:legacy` for the real historical data.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Leandro Cardoso',
            'email' => 'leandro@localchess.com',
            'password' => 'admin123',
        ]);

        app(CreateTeam::class)->handle($admin, 'Federación Marplatense de Ajedrez');
    }
}
