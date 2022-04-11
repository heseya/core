<?php

namespace App\Services;

use App\Dtos\ConsentDto;
use App\Models\Consent;
use App\Services\Contracts\ConsentServiceContract;

class ConsentService implements ConsentServiceContract
{
    public function store(ConsentDto $dto): Consent
    {
        return Consent::create($dto->toArray());
    }

    public function update(Consent $consent, ConsentDto $dto): Consent
    {
        $consent->update($dto->toArray());

        return $consent;
    }

    public function destroy(Consent $consent): void
    {
        $consent->delete();
    }
}
