<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\OrderDocument;

interface DocumentServiceContract
{
    public function storeDocument(Order $order, array $data): OrderDocument;

    public function removeDocument(Order $order, string $mediaId): OrderDocument;
}
