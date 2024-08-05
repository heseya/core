<?php

namespace Database\Seeders;

use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
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
            'status' => SalesChannelStatus::PUBLIC->value,
            'language_id' => Language::default()?->getKey(),
            'vat_rate' => '0',
            'activity' => SalesChannelActivityType::ACTIVE,
        ]);
        $published = [];
        foreach (Language::query()->get() as $language) {
            $channel->setLocale($language->getKey())->fill([
                'name' => 'Another',
            ]);
            $published[] = $language->getKey();
        }
        $channel->published = $published;
        $channel->save();
    }
}
