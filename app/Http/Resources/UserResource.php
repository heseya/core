<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
        ];
    }
}
