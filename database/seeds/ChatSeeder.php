<?php

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
        factory(Chat::class, 25)->create()->each(function ($chat) {
            $chat->messages()->saveMany(factory(Message::class, rand(1, 8))->make());
        });
    }
}
