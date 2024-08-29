<?php

declare(strict_types=1);

use Domain\Currency\Currency;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $default = app(SalesChannelRepository::class)->getDefault();
        $default->price_map_id = Currency::DEFAULT->getDefaultPriceMapId();
        $default->save();
    }

    public function down(): void {}
};
