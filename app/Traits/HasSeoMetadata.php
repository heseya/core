<?php

namespace App\Traits;

use Domain\Seo\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeoMetadata
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'modelSeo', 'model_type', 'model_id');
    }
}
