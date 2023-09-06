<?php

namespace App\Services;

use App\Dtos\RedirectCreateDto;
use App\Dtos\RedirectUpdateDto;
use App\Events\RedirectCreated;
use App\Events\RedirectDeleted;
use App\Events\RedirectUpdated;
use App\Models\Redirect;
use App\Services\Contracts\RedirectServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;

class RedirectService implements RedirectServiceContract
{
    public function getPaginated(): LengthAwarePaginator
    {
        return Redirect::query()->paginate(Config::get('pagination.per_page'));
    }

    public function create(RedirectCreateDto $dto): Redirect
    {
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
