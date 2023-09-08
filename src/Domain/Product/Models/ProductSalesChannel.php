<?php

declare(strict_types=1);

namespace Domain\Product\Models;

use App\Models\Product;
use Domain\Product\Enums\ProductSalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperProductSalesChannel
 */
final class ProductSalesChannel extends Pivot
{
    protected $casts = [
        'availability_status' => ProductSalesChannelStatus::class,
    ];

    /**
     * @param Builder<ProductSalesChannel> $query
     */
    public static function scopeForProductAndSalesChannel(Builder $query, Product $product, SalesChannel $salesChannel): void
    {
        $query->where('product_id', $product->getKey())->where('sales_channel_id', $salesChannel->getKey());
    }
}
