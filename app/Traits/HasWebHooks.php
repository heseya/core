<?php

namespace App\Traits;

use App\Models\WebHook;

trait HasWebHooks
{
    public function webhooks()
    {
        return $this->morphMany(WebHook::class, 'hasWebHooks', 'model_type', 'creator_id');
    }
}
