<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Traits\MapMetadata;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class SaleDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public readonly Missing|string $name;
    public readonly Missing|string|null $slug;
    public readonly Missing|string|null $description;
    public readonly Missing|string|null $description_html;

    public readonly string|Missing $percentage;
    /** @var PriceDto[] $amounts */
    public readonly array|Missing $amounts;

    public readonly int|Missing $priority;
    public readonly Missing|string $target_type;
    public readonly bool|Missing $target_is_allow_list;
    public readonly array|Missing $condition_groups;
    public readonly array|Missing $target_products;
    public readonly array|Missing $target_sets;
    public readonly array|Missing $target_shipping_methods;
    public readonly bool|Missing $active;

    public readonly array|Missing $metadata;
    public readonly Missing|SeoMetadataDto $seo;

    public function __construct(...$data)
    {
        if (!($data['percentage'] instanceof Missing || $data['amounts'] instanceof Missing)) {
            throw new DtoException("Can't have both percentage and amount discounts");
        }

        parent::__construct(...$data);
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function instantiateFromRequest(FormRequest|SaleCreateRequest $request): self
    {
        $amounts = $request->has('amounts') ? array_map(
            fn ($data) => PriceDto::fromData(...$data),
            $request->input('amounts'),
        ) : new Missing();

        return new self(
            name: $request->input('name', new Missing()),
            slug: $request->input('slug', new Missing()),
            description: $request->input('description', new Missing()),
            description_html: $request->input('description_html', new Missing()),
            percentage: $request->input('percentage') ?? new Missing(),
            amounts: $amounts,
            priority: $request->input('priority', new Missing()),
            target_type: $request->input('target_type', new Missing()),
            target_is_allow_list: $request->input('target_is_allow_list', new Missing()),
            condition_groups: self::mapConditionGroups($request->input('condition_groups', new Missing())),
            target_products: $request->input('target_products', new Missing()),
            target_sets: $request->input('target_sets', new Missing()),
            target_shipping_methods: $request->input('target_shipping_methods', new Missing()),
            active: $request->input('active', new Missing()),
            metadata: self::mapMetadata($request),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getConditionGroups(): array|Missing
    {
        return $this->condition_groups;
    }

    public function getTargetProducts(): array|Missing
    {
        return $this->target_products;
    }

    public function getTargetSets(): array|Missing
    {
        return $this->target_sets;
    }

    public function getTargetShippingMethods(): array|Missing
    {
        return $this->target_shipping_methods;
    }

    public function getSeo(): Missing|SeoMetadataDto
    {
        return $this->seo;
    }

    protected static function mapConditionGroups(array|Missing $conditionGroups): array|Missing
    {
        if ($conditionGroups instanceof Missing) {
            return $conditionGroups;
        }

        $conditionGroupDtos = [];

        foreach ($conditionGroups as $conditionGroup) {
            $conditionGroupDtos[] = ConditionGroupDto::fromArray($conditionGroup['conditions']);
        }

        return $conditionGroupDtos;
    }
}
