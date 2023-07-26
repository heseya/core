<?php

namespace App\Services\Contracts;

use App\DTO\OrderStatus\OrderStatusCreateDto;
use App\DTO\OrderStatus\OrderStatusUpdateDto;
use App\Models\Status;

interface StatusServiceContract
{
    public function store(OrderStatusCreateDto $dto): Status;

    public function update(Status $status, OrderStatusUpdateDto $dto): Status;

    public function destroy(Status $status): void;
}
