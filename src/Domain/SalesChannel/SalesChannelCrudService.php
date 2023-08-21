<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelIndexDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class SalesChannelCrudService
{
    public function __construct(
        private SalesChannelRepository $salesChannelRepository,
    ) {}

    public function show(string $id): SalesChannel
    {
        return $this->salesChannelRepository->getOne($id);
    }

    /**
     * @return LengthAwarePaginator<SalesChannel>
     */
    public function index(SalesChannelIndexDto $dto, bool $public_only): LengthAwarePaginator
    {
        if ($public_only) {
            return $this->salesChannelRepository->getAllPublic($dto);
        }

        return $this->salesChannelRepository->getAll($dto);
    }

    public function store(SalesChannelCreateDto $dto): SalesChannel
    {
        return $this->salesChannelRepository->store($dto);
    }

    public function update(string $id, SalesCHannelUpdateDto $dto): void
    {
        $this->salesChannelRepository->update($id, $dto);
    }

    public function delete(string $id): void
    {
        $this->salesChannelRepository->delete($id);
    }
}
