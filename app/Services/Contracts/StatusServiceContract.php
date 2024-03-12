<?php

namespace App\Services\Contracts;

use App\Models\Status;
use Domain\Order\Dtos\OrderStatusCreateDto;
use Domain\Order\Dtos\OrderStatusUpdateDto;

interface StatusServiceContract
{
    public function store(OrderStatusCreateDto $dto): Status;

    public function update(Status $status, OrderStatusUpdateDto $dto): Status;

    public function destroy(Status $status): void;
}
