<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

final class ErrorResource extends Resource
{
    /**
     * @var string
     */
    public static $wrap = 'error';

    public function base(Request $request): array
    {
        return [
            'message' => $this->resource->message,
            'code' => $this->resource->code,
            'key' => $this->resource->key,
            'errors' => $this->resource->errors,
        ];
    }
}
