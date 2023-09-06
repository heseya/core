<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\RedirectCreateRequest;
use App\Http\Requests\RedirectUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class RedirectUpdateDto extends Dto implements InstantiateFromRequest
{
    private string|Missing $name;
    private string|Missing $slug;
    private string|Missing $url;
    private int|Missing $type;

    public static function instantiateFromRequest(FormRequest|RedirectCreateRequest|RedirectUpdateRequest $request
    ): self {
        return new self(
            name: $request->has('name') ? $request->input('name') : new Missing(),
            slug: $request->has('slug') ? $request->input('slug') : new Missing(),
            url: $request->has('url') ? $request->input('url') : new Missing(),
            type: $request->has('type') ? $request->input('type') : new Missing(),
        );
    }
}
