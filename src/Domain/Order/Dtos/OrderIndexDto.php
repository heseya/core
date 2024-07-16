<?php

declare(strict_types=1);

namespace Domain\Order\Dtos;

use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrderIndexDto extends Data
{
    /**
     * @param string[]|Optional $ids
     * @param string[]|Optional $metadata
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Max(255)]
        public readonly Optional|string $search,
        #[Uuid]
        public readonly Optional|string $status_id,
        #[Uuid]
        public readonly Optional|string $shipping_method_id,
        #[Uuid]
        public readonly Optional|string $digital_shipping_method_id,
        #[Uuid]
        public readonly Optional|string $sales_channel_id,
        #[Uuid]
        public readonly Optional|string $payment_method_id,
        public readonly bool|Optional $paid,
        public readonly array|Optional $ids,
        public readonly array|Optional $metadata,
        public readonly array|Optional $metadata_private,
        #[Date]
        public readonly Optional|string $from,
        #[Date, AfterOrEqual('from')]
        public readonly Optional|string $to,
        public readonly string $sort = 'created_at:desc',
    ) {}
}
