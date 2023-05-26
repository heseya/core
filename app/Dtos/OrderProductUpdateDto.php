<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\OrderProductUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class OrderProductUpdateDto extends Dto implements InstantiateFromRequest
{
    private bool|Missing $is_delivered;
    private array|Missing $urls;

    public static function instantiateFromRequest(FormRequest|OrderProductUpdateRequest $request): self
    {
        return new self(
            is_delivered: $request->input('is_delivered', new Missing()),
            urls: self::processUrls($request->input('urls', new Missing())),
        );
    }

    public function getIsDelivered(): Missing|bool
    {
        return $this->is_delivered;
    }

    public function getUrls(): Missing|array
    {
        return $this->urls;
    }

    private static function processUrls(array|Missing $urls): array|Missing
    {
        if ($urls instanceof Missing) {
            return $urls;
        }

        $urlDtos = [];

        foreach ($urls as $key => $value) {
            $urlDtos[] = OrderProductUrlDto::init($key, $value);
        }

        return $urlDtos;
    }
}
