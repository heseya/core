<?php

namespace App\Services\Contracts;

use App\Models\Order;

interface DocumentServiceContract
{
    public function storeDocument(Order $order, array $data): Order;

    public function removeDocument(string $mediaId): void;
}
