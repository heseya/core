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
            'code' => $this->resource->code,
            'message' => $this->resource->message,
            'errors' => $this->resource->errors,
        ];
    }
}
