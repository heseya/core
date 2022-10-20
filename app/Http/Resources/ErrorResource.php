<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

final class ErrorResource extends Resource
{
    /**
     * @var string
     */
    public static $wrap = 'error';

    public function base(Request $request): array
    {
        $stack = [];

        // Show stack when app debug is on
        if (Config::get('app.debug') === true) {
            $stack = ['stack' => $this->resource->stack];
        }

        return [
            'message' => $this->resource->message,
            'code' => $this->resource->code,
            'key' => $this->resource->key,
            'errors' => $this->resource->errors,
        ] + $stack;
    }
}
