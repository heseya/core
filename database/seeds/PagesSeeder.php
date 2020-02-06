<?php

use App\Page;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Bezhanov\Faker\ProviderCollectionHelper;

class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create('pl_PL');
        ProviderCollectionHelper::addAllProvidersTo($faker);

        Page::create([
            'name' => 'FAQ',
            'slug' => 'faq',
            'public' => true,
            'content' => $faker->paragraph(),
        ]);

        Page::create([
            'name' => 'Requlamin',
            'slug' => 'statute',
            'public' => true,
            'content' => $faker->paragraph(),
        ]);
    }
}
