<?php

use App\Chat;
use App\Client;
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
        $faker = \Faker\Factory::create('pl_PL');

        for ($i = 1; $i <= 20; $i++) {

            $client = Client::create([
                'name' => $faker->name(),
            ]);

            $client->chats()->save(new Chat([
                'type' => rand(0, 2),
            ]));
        }
    }
}
