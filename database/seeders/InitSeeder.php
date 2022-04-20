<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SeoMetadata;
use Illuminate\Database\Seeder;

class InitSeeder extends Seeder
{
    public function run(): void
    {
        $this->status();

        $seeder = new CountriesSeeder;
        $seeder->run();

        $this->seo();
    }

    private function status() {
        DB::table('statuses')->insert([
            'id' => Str::uuid(),
            'name' => 'Nowe',
            'color' => 'ffd600',
            'description' => 'Twoje zamówienie zostało zapisane w systemie!',
        ]);

        DB::table('statuses')->insert([
            'id' => Str::uuid(),
            'name' => 'Wysłane',
            'color' => '1faa00',
            'description' => 'Zamówienie zostało wysłane i niedługo znajdzie się w Twoich rękach :)',
        ]);

        DB::table('statuses')->insert([
            'id' => Str::uuid(),
            'name' => 'Anulowane',
            'color' => 'a30000',
            'description' => 'Twoje zamówienie zostało anulowane, jeśli uważasz, że to błąd, skontaktuj się z nami.',
        ]);
    }

    private function seo()
    {
        $seo = SeoMetadata::create([
            'global' => true,
        ]);
        Cache::put('seo.global', $seo);
    }
}
