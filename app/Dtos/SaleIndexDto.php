<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\SaleIndexRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class SaleIndexDto extends Dto implements InstantiateFromRequest
{
    protected Missing|string $search;
    protected Missing|string $description;
    protected array|Missing $metadata;
    protected array|Missing $metadata_private;
    protected Missing|string $for_role;
    protected bool $coupon;
    protected array|Missing $ids;
    protected Missing|string $for_organization;

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
            for_organization: $request->input('for_organization', new Missing()),
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

    public function getMetadata(): array|Missing
    {
        return $this->metadata;
    }

    public function getMetadataPrivate(): array|Missing
    {
        return $this->metadata_private;
    }

    public function getIds(): array|Missing
    {
        return $this->ids;
    }

    private static function array(string $key, FormRequest|SaleIndexRequest $request): array|Missing
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
