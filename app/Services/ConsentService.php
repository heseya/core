<?php

namespace App\Services;

use App\Dtos\ConsentDto;
use App\Models\Consent;
use App\Models\ConsentUser;
use App\Models\User;
use App\Services\Contracts\ConsentServiceContract;
use Illuminate\Support\Collection;

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

    public function syncUserConsents(User $user, Collection $consents): void
    {
        $consents->each(function ($consent, $key) use ($user): void {
            $user->consents()->attach($key, ['value' => $consent]);
        });
    }

    public function updateUserConsents(?Collection $consents, User $user): void
    {
        if ($consents?->isNotEmpty()) {
            $consents->each(function ($value, $key) use ($user): void {
                ConsentUser::updateOrInsert(
                    [
                        'user_id' => $user->getKey(),
                        'consent_id' => $key,
                    ],
                    [
                        'value' => $value,
                    ],
                );
            });
        }
    }
}
