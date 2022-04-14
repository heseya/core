<?php

namespace App\Dtos;

use App\Http\Requests\SaleIndexRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class SaleIndexDto extends Dto
{
    protected string|Missing $search;
    protected string|Missing $description;
    protected array|Missing $metadata;
    protected array|Missing $metadata_private;
    protected bool $coupon;

    public static function fromFormRequest(SaleIndexRequest $request): self
    {
        return new self(
            search: $request->input('search', new Missing()),
            description: $request->input('description', new Missing()),
            metadata: self::array('metadata', $request),
            metadata_private: self::array('metadata_private', $request),
            coupon: false,
        );
    }

    public function getSearch(): Missing|string
    {
        return $this->search;
    }

    public function getDescription(): Missing|string
    {
        return $this->description;
    }

    public function getCoupon(): bool
    {
        return $this->coupon;
    }

    public function getMetadata(): Missing|array
    {
        return $this->metadata;
    }

    public function getMetadataPrivate(): Missing|array
    {
        return $this->metadata_private;
    }

    private static function array(string $key, SaleIndexRequest $request): array|Missing
    {
        if (!$request->has($key) || $request->input($key) === null) {
            return new Missing();
        }

        if (!is_array($request->input($key))) {
            return [$request->input($key)];
        }

        return $request->input($key);
    }
}
