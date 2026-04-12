<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run after setting ADMIN_DISCORD_ID in .env to make a user admin.
     * Usage: php artisan db:seed --class=AdminSeeder
     */
    public function run(): void
    {
        $discordId = env('ADMIN_DISCORD_ID');

        if (!$discordId) {
            $this->command->error('Set ADMIN_DISCORD_ID in your .env file first.');
            return;
        }

        $user = User::where('discord_id', $discordId)->first();

        if (!$user) {
            $this->command->error("No user found with Discord ID: {$discordId}. Log in with Discord first.");
            return;
        }

        $user->update(['role' => 'admin']);
        $this->command->info("Made {$user->name} an admin.");
    }
}
