<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use App\Models\App;
use App\Models\User;
use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelIndexDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

    public function update(SalesChannel $salesChannel, SalesChannelUpdateDto $dto): SalesChannel
    {
        return $this->salesChannelRepository->update($salesChannel, $dto);
    }

    public function delete(SalesChannel $salesChannel): void
    {
        $this->salesChannelRepository->delete($salesChannel);
    }

    public function getDefault(): SalesChannel
    {
        return $this->salesChannelRepository->getDefault();
    }

    public function userHasAccess(string $id): bool
    {
        /** @var SalesChannel|null $salesChannel */
        $salesChannel = SalesChannel::query()->where('id', '=', $id)->first();

        if (!$salesChannel) {
            return false;
        }

        if ($salesChannel->status === SalesChannelStatus::PRIVATE) {
            if (Gate::denies('sales_channels.show_hidden')) {
                return false;
            }

            /** @var App|User $user */
            $user = Auth::user();

            if ($salesChannel->hasOrganizationWithUser($user)) {
                return true;
            }
        }

        return true;
    }
}
