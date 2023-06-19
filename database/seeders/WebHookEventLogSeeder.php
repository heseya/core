<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WebHook;
use App\Models\WebHookEventLogEntry;
use Illuminate\Database\Seeder;

class WebHookEventLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        WebHook::factory()->count(20)->create([
            'creator_id' => $users->random(1)->first()->getKey(),
            'model_type' => User::class,
        ]);

        WebHookEventLogEntry::factory()->count(20)->create();
    }
}
