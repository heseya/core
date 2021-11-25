<?php

namespace App\Traits;

use App\Models\SeoMetadata;

trait HasSeoMetadata
{
    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'modelSeo', 'model_type', 'model_id');
    }
}
