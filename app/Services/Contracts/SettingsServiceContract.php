<?php

namespace App\Services\Contracts;

use App\Models\Setting;
use Brick\Money\Money;
use Illuminate\Support\Collection;

interface SettingsServiceContract
{
    public function getSettings(bool $publicOnly = false): Collection;

    public function getSetting(string $name): Setting;

    public function getMinimalPrice(string $name): Money;
}
