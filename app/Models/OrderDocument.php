<?php

namespace App\Models;

use App\Enums\OrderDocumentType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderDocument extends Pivot
{
    use HasUuid;

    protected $casts = [
        'type' => OrderDocumentType::class,
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
