<?php

namespace App\Models;

use App\Enums\MediaAttachmentType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperOrderDocument
 */
class OrderDocument extends Pivot
{
    use HasUuid;

    protected $casts = [
        'type' => MediaAttachmentType::class,
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
