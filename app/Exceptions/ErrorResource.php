<?php

namespace App\Exceptions;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class ErrorResource extends Resource
{
    /**
     * @var string
     */
    public static $wrap = 'error';

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     */
    public function base($request): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }
}
