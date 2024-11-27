<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Services;

use App\Models\Product;
use Domain\Manufacturer\Dtos\ManufacturerCreateDto;
use Domain\Manufacturer\Dtos\ManufacturerIndexDto;
use Domain\Manufacturer\Dtos\ManufacturerUpdateDto;
use Domain\Manufacturer\Models\Manufacturer;
use Domain\Manufacturer\Repositories\ManufacturerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Optional;

final readonly class ManufacturerService
{
    public function __construct(
        private ManufacturerRepository $manufacturerRepository,
    ) {}

    /**
     * @return LengthAwarePaginator<Manufacturer>
     */
    public function index(ManufacturerIndexDto $dto): LengthAwarePaginator
    {
        return $this->manufacturerRepository->index($dto);
    }

    public function create(ManufacturerCreateDto $dto): Manufacturer
    {
        $manufacturer = $this->manufacturerRepository->create($dto);

        if (!$dto->product_ids instanceof Optional) {
            Product::query()->whereIn('id', $dto->product_ids)->update(['manufacturer_id' => $manufacturer->getKey()]);
            $manufacturer->refresh();
        }

        return $manufacturer;
    }

    public function update(Manufacturer $manufacturer, ManufacturerUpdateDto $dto): Manufacturer
    {
        $manufacturer = $this->manufacturerRepository->update($manufacturer, $dto);

        if (!$dto->product_ids instanceof Optional) {
            Product::query()->whereIn('id', $dto->product_ids)->update(['manufacturer_id' => $manufacturer->getKey()]);

            $manufacturer->products()->whereNotIn('id', $dto->product_ids)->update(['manufacturer_id' => null]);

            $manufacturer->refresh();
        }

        return $manufacturer;
    }

    public function destroy(Manufacturer $manufacturer): void
    {
        $this->manufacturerRepository->destroy($manufacturer);
    }
}
