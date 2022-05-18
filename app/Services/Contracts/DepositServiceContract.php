<?php

namespace App\Services\Contracts;

use App\Models\Item;

interface DepositServiceContract
{
    public function getDepositsGroupByDateForItem(Item $item, string $order = 'ASC'): array;

    public function getDepositsGroupByTimeForItem(Item $item, string $order = 'ASC'): array;
}
