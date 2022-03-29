<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\OrderDocument;
use Illuminate\Http\UploadedFile;

interface DocumentServiceContract
{
    public function storeDocument(Order $order, ?string $name, string $type, UploadedFile $file): OrderDocument;

    public function downloadDocument(OrderDocument $document);

    public function removeDocument(Order $order, string $mediaId): OrderDocument;
}
