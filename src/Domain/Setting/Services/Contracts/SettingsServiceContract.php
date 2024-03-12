<?php

declare(strict_types=1);

namespace Domain\Setting\Services\Contracts;

use Domain\Setting\Models\Setting;
use Illuminate\Support\Collection;

interface SettingsServiceContract
{
    /**
     * @return Collection<int, Setting>
     */
    public function getSettings(bool $publicOnly = false): Collection;

    public function getSetting(string $name): Setting;

    public function getMinimalPrice(string $name): float;

    /**
     * @return string[]
     */
    public function getAdminMails(): array;
}
