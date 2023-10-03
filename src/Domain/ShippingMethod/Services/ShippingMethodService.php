<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\StoreException;
use App\Models\Address;
use App\Models\User;
use App\Services\Contracts\MetadataServiceContract;
use Brick\Money\Money;
use Domain\ShippingMethod\Dtos\PriceRangeDto;
use Domain\ShippingMethod\Dtos\ShippingMethodCreateDto;
use Domain\ShippingMethod\Dtos\ShippingMethodIndexDto;
use Domain\ShippingMethod\Dtos\ShippingMethodUpdateDto;
use Domain\ShippingMethod\Models\ShippingMethod;
use Domain\ShippingMethod\Services\Contracts\ShippingMethodServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Optional;

final readonly class ShippingMethodService implements ShippingMethodServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {}

    public function index(ShippingMethodIndexDto $dto, ?Money $cartValue): LengthAwarePaginator
    {
        $search = $dto->only(
            'metadata',
            'metadata_private',
            'ids',
            'items',
            'sales_channel_id',
        )->toArray();

        $query = ShippingMethod::query()
            ->searchByCriteria($search)
            ->with('metadata')
            ->orderBy('order');

        /** @var User $user */
        $user = Auth::user();

        if (!$user->can('shipping_methods.show_hidden')) {
            $query->where('public', true);
        }

        if ($user->hasAnyPermission([
            'payment_methods.show_hidden',
            'shipping_methods.edit',
        ])) {
            $query->with('paymentMethods');
        } else {
            $query->with([
                'paymentMethods' => fn ($query) => $query->where('public', true),
            ]);
        }

        $country = $dto->country;

        if (!$country instanceof Optional) {
            $query->where(function (Builder $query) use ($country): void {
                $query->where(function (Builder $query) use ($country): void {
                    $query
                        ->where('is_block_list_countries', false)
                        ->whereHas('countries', fn ($query) => $query->where('code', $country));
                })->orWhere(function (Builder $query) use ($country): void {
                    $query
                        ->where('is_block_list_countries', true)
                        ->whereDoesntHave(
                            'countries',
                            fn ($query) => $query->where('code', $country),
                        );
                });
            });
        }

        $shippingMethods = $query->paginate(Config::get('pagination.per_page'));
        $shippingMethods->each(
            fn (ShippingMethod $method) => $method->prices = $cartValue ?
                [$method->getPrice($cartValue)] :
                $method->getStartingPrices(),
        );

        return $shippingMethods;
    }

    public function store(ShippingMethodCreateDto $shippingMethodDto): ShippingMethod
    {
        $attributes = array_merge(
            $shippingMethodDto->toArray(),
            ['order' => ShippingMethod::query()->count()],
        );

        /** @var ShippingMethod $shippingMethod */
        $shippingMethod = ShippingMethod::query()->create($attributes);

        if (!$shippingMethodDto->getShippingPoints() instanceof Optional) {
            $this->syncShippingPoints($shippingMethodDto, $shippingMethod);
        }

        if (!$shippingMethod->payment_on_delivery && !$shippingMethodDto->getPaymentMethods() instanceof Optional) {
            $shippingMethod->paymentMethods()->sync($shippingMethodDto->getPaymentMethods());
        }

        if (!$shippingMethodDto->getCountries() instanceof Optional) {
            $shippingMethod->countries()->sync($shippingMethodDto->getCountries());
        }
        if (!($shippingMethodDto->sales_channels instanceof Optional)) {
            $shippingMethod->salesChannels()->sync($shippingMethodDto->sales_channels);
        }

        if (!($shippingMethodDto->metadata_computed instanceof Optional)) {
            $this->metadataService->sync($shippingMethod, $shippingMethodDto->metadata_computed);
        }

        if (!($shippingMethodDto->product_ids instanceof Optional)) {
            $shippingMethod->products()->sync($shippingMethodDto->product_ids);
        }

        if (!($shippingMethodDto->product_set_ids instanceof Optional)) {
            $shippingMethod->productSets()->sync($shippingMethodDto->product_set_ids);
        }

        $shippingMethodDto->getPriceRanges()->each(
            fn (PriceRangeDto $range) => $shippingMethod->priceRanges()->firstOrCreate([
                'start' => $range->start,
                'value' => $range->value,
                'currency' => $range->value->getCurrency(),
            ]),
        );

        return $shippingMethod;
    }

    public function update(ShippingMethod $shippingMethod, ShippingMethodUpdateDto $shippingMethodDto): ShippingMethod
    {
        $shippingMethod->update(
            $shippingMethodDto->toArray(),
        );

        if ($shippingMethodDto->getShippingPoints() !== null) {
            $this->syncShippingPoints($shippingMethodDto, $shippingMethod);
        }

        if (!$shippingMethod->payment_on_delivery && !$shippingMethodDto->getPaymentMethods() instanceof Optional) {
            $shippingMethod->paymentMethods()->sync($shippingMethodDto->getPaymentMethods());
        } elseif ($shippingMethod->payment_on_delivery) {
            $shippingMethod->paymentMethods()->sync([]);
        }

        if (!$shippingMethodDto->getCountries() instanceof Optional) {
            $shippingMethod->countries()->sync($shippingMethodDto->getCountries());
        }

        if (!$shippingMethodDto->getPriceRanges() instanceof Optional) {
            $shippingMethod->priceRanges()->delete();

            foreach ($shippingMethodDto->getPriceRanges() as $range) {
                $shippingMethod->priceRanges()->firstOrCreate([
                    'start' => $range->start,
                    'value' => $range->value,
                    'currency' => $range->value->getCurrency(),
                ]);
            }
        }

        if (!($shippingMethodDto->product_ids instanceof Optional)) {
            $shippingMethod->products()->sync($shippingMethodDto->product_ids);
        }

        if (!($shippingMethodDto->product_set_ids instanceof Optional)) {
            $shippingMethod->productSets()->sync($shippingMethodDto->product_set_ids);
        }

        if (!($shippingMethodDto->sales_channels instanceof Optional)) {
            $shippingMethod->salesChannels()->sync($shippingMethodDto->sales_channels);
        }

        return $shippingMethod;
    }

    public function reorder(array $shippingMethods): void
    {
        foreach ($shippingMethods as $key => $id) {
            ShippingMethod::where('id', $id)->update(['order' => $key]);
        }
    }

    public function destroy(ShippingMethod $shippingMethod): void
    {
        if (!$shippingMethod->deletable) {
            throw new StoreException(Exceptions::CLIENT_SHIPPING_METHOD_NOT_OWNER);
        }
        if ($shippingMethod->orders()->count() === 0) {
            $shippingMethod->forceDelete();
        }
        $shippingMethod->delete();
    }

    private function syncShippingPoints(
        ShippingMethodCreateDto|ShippingMethodUpdateDto $shippingMethodDto,
        ShippingMethod $shippingMethod,
    ): void {
        $shippingPoints = $shippingMethodDto->getShippingPoints();

        if (!is_array($shippingPoints)) {
            $shippingMethod->shippingPoints()->sync([]);
        }

        $addresses = new Collection();

        // @phpstan-ignore-next-line
        foreach ($shippingPoints as $shippingPoint) {
            if (array_key_exists('id', $shippingPoint)) {
                Address::query()->where('id', $shippingPoint['id'])->update($shippingPoint);
                /** @var Address $address */
                $address = Address::query()->findOrFail($shippingPoint['id']);
            } else {
                /** @var Address $address */
                $address = Address::query()->create($shippingPoint);
            }
            $addresses->push($address->getKey());
        }

        $shippingMethod->shippingPoints()->sync($addresses);
    }
}
