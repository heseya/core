<?php

namespace Domain\Consent\Repositories;

use Domain\Consent\Dtos\ConsentCreateDto;
use Domain\Consent\Dtos\ConsentUpdateDto;
use Domain\Consent\Models\Consent;
use Spatie\LaravelData\Optional;

final readonly class ConsentRepository
{
    public function create(ConsentCreateDto $dto): Consent
    {
        /** @var Consent $consent */
        $consent = Consent::query()->make($dto->toArray());

        foreach ($dto->translations as $lang => $translation) {
            $consent->setLocale($lang)->fill($translation);
        }

        $consent->save();
        return $consent;
    }

    public function update(string $id, ConsentUpdateDto $dto): Consent
    {
        /** @var Consent $consent */
        $consent = Consent::query()->where('id', '=', $id)->firstOrFail();

        $consent->fill($dto->toArray());
        if (!$dto->translations instanceof Optional) {
            foreach ($dto->translations as $lang => $translation) {
                $consent->setLocale($lang)->fill($translation);
            }
        }

        $consent->save();
        return $consent;
    }
}
