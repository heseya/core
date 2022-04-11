<?php

namespace App\Services\Contracts;

use App\Dtos\ConsentDto;
use App\Models\Consent;

interface ConsentServiceContract
{
    public function store(ConsentDto $dto): Consent;

    public function update(Consent $consent, ConsentDto $dto): Consent;

    public function destroy(Consent $consent): void;
}
