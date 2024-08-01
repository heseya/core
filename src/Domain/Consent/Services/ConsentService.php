<?php

declare(strict_types=1);

namespace Domain\Consent\Services;

use App\Models\User;
use Domain\Consent\Dtos\ConsentCreateDto;
use Domain\Consent\Dtos\ConsentIndexDto;
use Domain\Consent\Dtos\ConsentUpdateDto;
use Domain\Consent\Models\Consent;
use Domain\Consent\Models\ConsentOrganization;
use Domain\Consent\Models\ConsentUser;
use Domain\Consent\Repositories\ConsentRepository;
use Domain\Organization\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

final readonly class ConsentService
{
    public function __construct(
        private readonly ConsentRepository $consentRepository,
    ) {}

    /**
     * @return LengthAwarePaginator<Consent>
     */
    public function index(ConsentIndexDto $dto): LengthAwarePaginator
    {
        return Consent::searchByCriteria($dto->toArray())->paginate(Config::get('pagination.per_page'));
    }

    public function store(ConsentCreateDto $dto): Consent
    {
        return $this->consentRepository->create($dto);
    }

    public function update(Consent $consent, ConsentUpdateDto $dto): Consent
    {
        return $this->consentRepository->update($consent->getKey(), $dto);
    }

    public function destroy(Consent $consent): void
    {
        $consent->delete();
    }

    /**
     * @param array<string, bool> $consents
     */
    public function syncUserConsents(User $user, array $consents): void
    {
        foreach ($consents as $key => $consent) {
            $user->consents()->attach($key, ['value' => $consent]);
        }
    }

    /**
     * @param Collection<string, bool>|null $consents
     */
    public function updateUserConsents(?Collection $consents, User $user): void
    {
        if ($consents?->isNotEmpty()) {
            $consents->each(fn ($value, $key) => ConsentUser::updateOrInsert(
                [
                    'user_id' => $user->getKey(),
                    'consent_id' => $key,
                ],
                [
                    'value' => $value,
                ],
            ));
        }
    }

    /**
     * @param array<string, bool> $consents
     */
    public function syncOrganizationConsents(Organization $organization, array $consents): void
    {
        foreach ($consents as $key => $consent) {
            $organization->consents()->attach($key, ['value' => $consent]);
        }
    }

    /**
     * @param Collection<string, bool> $consents
     */
    public function updateOrganizationConsents(Collection $consents, Organization $organization): void
    {
        if ($consents->isNotEmpty()) {
            $consents->each(fn ($value, $consent) => ConsentOrganization::updateOrInsert(
                [
                    'organization_id' => $organization->getKey(),
                    'consent_id' => $consent,
                ],
                [
                    'value' => $value,
                ],
            ));
        }
    }
}
