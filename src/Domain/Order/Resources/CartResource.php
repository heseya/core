<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use Carbon\Carbon;
use Domain\Currency\Currency;
use Domain\Order\Dtos\OrderPriceDto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\DataWithGlobalMetadata;
use Support\LaravelData\Transformers\WithoutWrappingTransformer;

final class CartResource extends DataWithGlobalMetadata
{
    /**
     * @param DataCollection<int,CartItemResource> $items
     * @param DataCollection<int,CouponShortResource> $coupons
     * @param DataCollection<int,SalesShortResource> $sales
     */
    public function __construct(
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(CartItemResource::class)]
        public DataCollection $items,
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(CouponShortResource::class)]
        public DataCollection $coupons,
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(SalesShortResource::class)]
        public DataCollection $sales,
        public OrderPriceDto $cart_total_initial,
        public OrderPriceDto $cart_total,
        public OrderPriceDto $shipping_price_initial,
        public OrderPriceDto $shipping_price,
        public OrderPriceDto $summary,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public ?float $shipping_time = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public Carbon|string|null $shipping_date = null,
    ) {}

    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
