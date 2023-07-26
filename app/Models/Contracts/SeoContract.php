<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface SeoContract
{
    public function seo(): MorphOne;
}
