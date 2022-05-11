<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class StatusDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private string|Missing $name;
    private string|Missing $color;
    private bool|Missing $cancel;
    private string|Missing $description;
    private bool|Missing $hidden;
    private bool|Missing $no_notifications;

    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            color: $request->input('color', new Missing()),
            cancel: $request->input('cancel', new Missing()),
            description: $request->input('description', new Missing()),
            hidden: $request->input('hidden', new Missing()),
            no_notifications: $request->input('no_notifications', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }
}
