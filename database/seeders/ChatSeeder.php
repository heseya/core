<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Chat::factory()->count(25)->create()->each(function ($chat) {
            $chat->messages()->saveMany(Message::factory()->count(rand(1, 8))->make());
        });
    }
}
