<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use App\Models\WebHook;
use App\Models\WebHookEventLogEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WebHookEventLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
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
