<?php

namespace App\Services\Contracts;

use App\Models\Schema;

interface OptionServiceContract
{
    public function sync(Schema $schema, array $options = []): void;
}
