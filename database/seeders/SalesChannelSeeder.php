<?php

namespace Database\Seeders;

use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Seeder;
use Support\Enum\Status;

class SalesChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channel = SalesChannel::query()->make([
            'slug' => 'another',
            'status' => Status::ACTIVE->value,
            'countries_block_list' => false,
            'default_currency' => Currency::EUR,
            'default_language_id' => Language::default()?->getKey(),
            'vat_rate' => '0',
        ]);
        foreach (Language::query()->get() as $language) {
            $channel->setLocale($language->getKey())->fill([
                'name' => 'Another',
            ]);
        }
        $channel->save();
    }
}
