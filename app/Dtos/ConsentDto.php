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
    private string|Missing $name;
    private string|Missing $description_html;
    private bool|Missing $required;

    public static function instantiateFromRequest(FormRequest|ConsentStoreRequest|ConsentUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            description_html: $request->input('description_html', new Missing()),
            required: $request->input('required', new Missing()),
        );
    }

    public function getName(): string|Missing
    {
        return $this->name;
    }

    public function getDescriptionHtml(): string|Missing
    {
        return $this->description_html;
    }

    public function getRequired(): bool|Missing
    {
        return $this->required;
    }
}
