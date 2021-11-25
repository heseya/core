<?php

namespace App\Http\Requests;

use App\Traits\SeoMetadataRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class SeoMetadataRulesRequest extends FormRequest
{
    use SeoMetadataRules;

    public function rulesWithSeo(array $array): array
    {
        return array_merge($array, $this->seoRules());
    }
}
