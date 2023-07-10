<?php

namespace App\Services\Contracts;

use App\DTO\OrderStatus\OrderStatusDto;
use App\Models\Status;

interface StatusServiceContract
{
    public function store(OrderStatusDto $dto): Status;

    public function update(Status $status, OrderStatusDto $dto): Status;

    public function destroy(Status $status): void;
}
