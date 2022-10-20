<?php

namespace App\Services\Contracts;

use Heseya\Dto\Missing;

interface DiscountStoreServiceContract
{
    public function mapConditionGroups(array|Missing $conditionGroups): array|Missing;
}
