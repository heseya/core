<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use Brick\Money\Money;
use Carbon\Carbon;
use Domain\Currency\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\DataWithGlobalMetadata;
use Support\LaravelData\Casts\MoneyCast;
use Support\LaravelData\Transformers\MoneyToAmountTransformer;

final class CartResource extends DataWithGlobalMetadata
{
    /**
     * @param DataCollection<int,CartItemResource> $items
     * @param DataCollection<int,CouponShortResource> $coupons
     * @param DataCollection<int,SalesShortResource> $sales
     */
    public function __construct(
        #[DataCollectionOf(CartItemResource::class)]
        public DataCollection $items,
        #[DataCollectionOf(CouponShortResource::class)]
        public DataCollection $coupons,
        #[DataCollectionOf(SalesShortResource::class)]
        public DataCollection $sales,
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $cart_total_initial,
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $cart_total,
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $shipping_price_initial,
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $shipping_price,
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $summary,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public ?float $shipping_time = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?Carbon $shipping_date = null,
    ) {}

    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
