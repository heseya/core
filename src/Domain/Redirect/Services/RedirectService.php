<?php

namespace Domain\Redirect\Services;

use Domain\Redirect\Dtos\RedirectCreateDto;
use Domain\Redirect\Dtos\RedirectIndexDto;
use Domain\Redirect\Dtos\RedirectUpdateDto;
use Domain\Redirect\Events\RedirectCreated;
use Domain\Redirect\Events\RedirectDeleted;
use Domain\Redirect\Events\RedirectUpdated;
use Domain\Redirect\Models\Redirect;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;

class RedirectService
{
    public function getPaginated(RedirectIndexDto $dto): LengthAwarePaginator
    {
        return Redirect::searchByCriteria($dto->toArray())->paginate(Config::get('pagination.per_page'));
    }

    public function create(RedirectCreateDto $dto): Redirect
    {
        /** @var Redirect $redirect */
        $redirect = Redirect::query()->create($dto->toArray());

        RedirectCreated::dispatch($redirect);

        return $redirect;
    }

    public function delete(Redirect $redirect): void
    {
        if ($redirect->delete()) {
            RedirectDeleted::dispatch($redirect);
        }
    }

    public function update(Redirect $redirect, RedirectUpdateDto $dto): Redirect
    {
        $redirect->update($dto->toArray());

        RedirectUpdated::dispatch($redirect);

        return $redirect;
    }
}
