<?php

namespace App\Services;

use App\Dtos\ShippingMethodCreateDto;
use App\Dtos\ShippingMethodUpdateDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\StoreException;
use App\Models\Address;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\ShippingMethodServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class ShippingMethodService implements ShippingMethodServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {
    }

    public function index(?array $search, ?string $country, float $cartValue): LengthAwarePaginator
    {
        $query = ShippingMethod::query()
            ->searchByCriteria($search ?? [])
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

        if ($country) {
            $query->where(function (Builder $query) use ($country): void {
                $query->where(function (Builder $query) use ($country): void {
                    $query
                        ->where('block_list', false)
                        ->whereHas('countries', fn ($query) => $query->where('code', $country));
                })->orWhere(function (Builder $query) use ($country): void {
                    $query
                        ->where('block_list', true)
                        ->whereDoesntHave(
                            'countries',
                            fn ($query) => $query->where('code', $country),
                        );
                });
            });
        }

        $shippingMethods = $query->paginate(Config::get('pagination.per_page'));
        $shippingMethods->each(fn (ShippingMethod $method) => $method->price = $method->getPrice($cartValue));

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

        if ($shippingMethodDto->getShippingPoints() !== null) {
            $this->syncShippingPoints($shippingMethodDto, $shippingMethod);
        }

        if ($shippingMethodDto->getPaymentMethods() !== null) {
            $shippingMethod->paymentMethods()->sync($shippingMethodDto->getPaymentMethods());
        }

        if ($shippingMethodDto->getCountries() !== null) {
            $shippingMethod->countries()->sync($shippingMethodDto->getCountries());
        }

        if (!($shippingMethodDto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($shippingMethod, $shippingMethodDto->getMetadata());
        }

        $priceRanges = $shippingMethodDto->getPriceRanges() !== null ? $shippingMethodDto->getPriceRanges() : [];
        foreach ($priceRanges as $range) {
            $priceRange = $shippingMethod->priceRanges()->firstOrCreate([
                'start' => $range['start'],
            ]);
            $priceRange->prices()->create([
                'value' => $range['value'],
            ]);
        }

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

        if ($shippingMethodDto->getPaymentMethods() !== null) {
            $shippingMethod->paymentMethods()->sync($shippingMethodDto->getPaymentMethods());
        }

        if ($shippingMethodDto->getCountries() !== null) {
            $shippingMethod->countries()->sync($shippingMethodDto->getCountries());
        }

        if ($shippingMethodDto->getPriceRanges() !== null) {
            $shippingMethod->priceRanges()->delete();

            foreach ($shippingMethodDto->getPriceRanges() as $range) {
                $priceRange = $shippingMethod->priceRanges()->firstOrCreate([
                    'start' => $range['start'],
                ]);
                $priceRange->prices()->create([
                    'value' => $range['value'],
                ]);
            }
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
        if ($shippingMethod->orders()->count() > 0) {
            throw new ClientException(Exceptions::CLIENT_DELETE_WHEN_RELATION_EXISTS);
        }
        if (!$shippingMethod->deletable) {
            throw new StoreException(Exceptions::CLIENT_SHIPPING_METHOD_NOT_OWNER);
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
