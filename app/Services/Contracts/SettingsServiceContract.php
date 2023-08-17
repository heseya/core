<?php

namespace App\Services\Contracts;

use App\Models\Setting;
use Domain\Currency\Currency;
use Illuminate\Support\Collection;

interface SettingsServiceContract
{
    public function getSettings(bool $publicOnly = false): Collection;

    public function getSetting(string $name): Setting;

    public function getMinimalPrice(string $name = 'minimal_product_price', Currency $currency = Currency::DEFAULT): float;
}
