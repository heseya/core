<?php

namespace App\Exceptions;

use Illuminate\Http\Resources\Json\JsonResource;

final class ErrorResource extends JsonResource
{
    /**
     * @var string
     */
    public static $wrap = 'error';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
