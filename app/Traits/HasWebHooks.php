<?php

namespace App\Traits;

use App\Models\WebHook;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWebHooks
{
    public function webhooks(): MorphMany
    {
        return $this->morphMany(WebHook::class, 'hasWebHooks', 'model_type', 'creator_id');
    }
}
