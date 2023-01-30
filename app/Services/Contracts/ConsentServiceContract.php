<?php

namespace App\Services\Contracts;

use App\Dtos\ConsentDto;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Support\Collection;

interface ConsentServiceContract
{
    public function store(ConsentDto $dto): Consent;

    public function update(Consent $consent, ConsentDto $dto): Consent;

    public function destroy(Consent $consent): void;

    public function syncUserConsents(User $user, Collection $consents): void;

    public function updateUserConsents(?Collection $consents, User $user): void;
}
