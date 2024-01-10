<?php

use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $salesChannels = SalesChannel::query()->whereNull('published');
        /** @var SalesChannel $saleChannel */
        foreach ($salesChannels->cursor() as $saleChannel) {
            $translations = $saleChannel->getTranslations('name');
            $saleChannel->published = array_keys($translations);
            $saleChannel->save();
        }
    }

    public function down(): void
    {
    }
};
