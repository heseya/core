<?php

namespace App\Dtos\Contracts;

use Illuminate\Foundation\Http\FormRequest;

interface InstantiateFromRequest
{
    public static function instantiateFromRequest(FormRequest $request): self;
}
