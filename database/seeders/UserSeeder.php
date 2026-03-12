<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Laravel\Sanctum\PersonalAccessToken;

class UserSeeder extends Seeder
{
    private const DEMO_TOKEN = 'deadbeef-wildcard-demo-token';

    /**
     * Seed the demo user with a static API token.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        PersonalAccessToken::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'demo-token',
            'token' => hash('sha256', self::DEMO_TOKEN),
            'abilities' => ['*'],
        ]);

        $this->command->info('Demo API token: '.self::DEMO_TOKEN);
    }
}
