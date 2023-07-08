<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\ConsentStoreRequest;
use App\Http\Requests\ConsentUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ConsentDto extends Dto implements InstantiateFromRequest
{
    private Missing|string $name;
    private Missing|string $description_html;
    private bool|Missing $required;

    public static function instantiateFromRequest(ConsentStoreRequest|ConsentUpdateRequest|FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            description_html: $request->input('description_html', new Missing()),
            required: $request->input('required', new Missing()),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getDescriptionHtml(): Missing|string
    {
        return $this->description_html;
    }

    public function getRequired(): bool|Missing
    {
        return $this->required;
    }
}
