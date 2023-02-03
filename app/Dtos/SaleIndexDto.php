<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\SaleIndexRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class SaleIndexDto extends Dto implements InstantiateFromRequest
{
    protected string|Missing $search;
    protected string|Missing $description;
    protected array|Missing $metadata;
    protected array|Missing $metadata_private;
    protected string|Missing $for_role;
    protected bool $coupon;
    protected array|Missing $ids;

    public static function instantiateFromRequest(FormRequest|SaleIndexRequest $request): self
    {
        return new self(
            search: $request->input('search', new Missing()),
            description: $request->input('description', new Missing()),
            metadata: self::array('metadata', $request),
            metadata_private: self::array('metadata_private', $request),
            for_role: $request->input('for_role', new Missing()),
            coupon: false,
            ids: $request->input('ids', new Missing()),
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

    public function getIds(): Missing|array
    {
        return $this->ids;
    }
}
