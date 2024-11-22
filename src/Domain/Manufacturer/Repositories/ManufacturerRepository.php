<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Repositories;

use App\Models\Address;
use Domain\Manufacturer\Dtos\ManufacturerCreateDto;
use Domain\Manufacturer\Dtos\ManufacturerIndexDto;
use Domain\Manufacturer\Dtos\ManufacturerUpdateDto;
use Domain\Manufacturer\Models\Manufacturer;
use Domain\User\Dtos\AddressStoreDto;
use Domain\User\Dtos\AddressUpdateDto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;

final class ManufacturerRepository
{
    /**
     * @return LengthAwarePaginator<Manufacturer>
     */
    public function index(ManufacturerIndexDto $dto): LengthAwarePaginator
    {
        return Manufacturer::searchByCriteria($dto->toArray())->paginate(Config::get('pagination.per_page'));
    }

    public function create(ManufacturerCreateDto $dto): Manufacturer
    {
        $address = Address::query()->firstOrCreate($dto->address->toArray());

        return Manufacturer::query()->create(array_merge($dto->toArray(), ['address_id' => $address->getKey()]));
    }

    public function update(Manufacturer $manufacturer, ManufacturerUpdateDto $dto): Manufacturer
    {
        if ($dto->address instanceof AddressUpdateDto) {
            $manufacturer->address()->update($dto->address->toArray());
        }

        $manufacturer->update($dto->toArray());

        return $manufacturer;
    }

    public function destroy(Manufacturer $manufacturer): void
    {
        $manufacturer->delete();
    }
}
