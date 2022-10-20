<?php

namespace App\Http\Requests;

use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class PackageTemplateCreateRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'name' => ['required', 'string', 'max:255'],
                'weight' => ['required', 'numeric'],
                'width' => ['required', 'integer'],
                'height' => ['required', 'integer'],
                'depth' => ['required', 'integer'],
            ]
        );
    }
}
