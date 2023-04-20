<?php

namespace App\Models;

use App\Enums\MediaAttachmentType;
use App\Enums\VisibilityType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $name
 * @property MediaAttachmentType $type
 * @property string|null $label
 * @property VisibilityType $visibility
 * @property Media $media
 * @mixin IdeHelperMediaAttachment
 */
class MediaAttachment extends Pivot
{
    use HasUuid;
    protected $table = 'media_attachments';

    protected $fillable = [
        'name',
        'type',
        'label',
        'visibility',
        'model_id',
        'model_type',
        'media_id',
    ];

    protected $casts = [
        'visibility' => VisibilityType::class,
        'type' => MediaAttachmentType::class,
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model', 'model_type', 'model_id', 'id');
    }
}
