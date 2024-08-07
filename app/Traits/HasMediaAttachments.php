<?php

namespace App\Traits;

use App\Models\MediaAttachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMediaAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'model')
            // Label is used for grouping attachments in different relations
            ->whereNull('label');
    }
}
