<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\RedirectCreateRequest;
use App\Http\Requests\RedirectUpdateRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class RedirectCreateDto extends Dto implements InstantiateFromRequest
{
    private string $name;
    private string $slug;
    private string $url;
    private int $type;

    public static function instantiateFromRequest(FormRequest|RedirectCreateRequest|RedirectUpdateRequest $request
    ): self {
        return new self(
            name: $request->input('name'),
            slug: $request->input('slug'),
            url: $request->input('url'),
            type: $request->input('type'),
        );
    }
}
