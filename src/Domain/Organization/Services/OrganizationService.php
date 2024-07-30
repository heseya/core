<?php

declare(strict_types=1);

namespace Domain\Organization\Services;

use App\DTO\Auth\RegisterDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SavedAddressType;
use App\Events\OrganizationCreated;
use App\Exceptions\ClientException;
use App\Mail\OrganizationRegistered;
use App\Models\User;
use Domain\Consent\Services\ConsentService;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationPublicUpdateDto;
use Domain\Organization\Dtos\OrganizationRegisterDto;
use Domain\Organization\Dtos\OrganizationSavedAddressCreateDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Models\Organization;
use Domain\Organization\Repositories\OrganizationRepository;
use Domain\User\Services\AuthService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

final readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OrganizationSavedAddressService $organizationSavedAddressService,
        private AuthService $authService,
        private ConsentService $consentService,
    ) {}

    /**
     * @return LengthAwarePaginator<Organization>
     */
    public function index(OrganizationIndexDto $dto): LengthAwarePaginator
    {
        return $this->organizationRepository->index($dto);
    }

    public function show(string $id): Organization
    {
        return $this->organizationRepository->show($id);
    }

    public function create(OrganizationCreateDto $dto): Organization
    {
        $organization = $this->organizationRepository->create($dto);

        $forceDefault = count($dto->shipping_addresses) === 1;
        /** @var OrganizationSavedAddressCreateDto $shipping_address */
        foreach ($dto->shipping_addresses as $shipping_address) {
            $this->organizationSavedAddressService->storeAddress($shipping_address, SavedAddressType::SHIPPING, $organization->getKey(), $forceDefault);
        }

        $this->consentService->syncOrganizationConsents($organization, $dto->consents);
        $organization->refresh();

        OrganizationCreated::dispatch($organization);

        return $organization;
    }

    public function update(Organization $organization, OrganizationUpdateDto $dto): Organization
    {
        // TODO dodać consents
        return $this->organizationRepository->update($organization, $dto);
    }

    public function delete(Organization $organization): void
    {
        $this->organizationRepository->delete($organization);
    }

    public function register(OrganizationRegisterDto $dto): Organization
    {
        return DB::transaction(function () use ($dto): Organization {
            $organization = $this->organizationRepository->registerOrganization($dto);

            $user = $this->authService->register(RegisterDto::from([
                'email' => $dto->creator_email,
                'password' => $dto->creator_password,
                'name' => $dto->creator_name,
                'captcha_token' => $dto->captcha_token,
            ]));

            $organization->users()->attach($user->getKey());

            $this->consentService->syncOrganizationConsents($organization, $dto->consents);

            OrganizationCreated::dispatch($organization);

            $this->sendNewOrganizationAlert($organization);

            return $organization;
        });
    }

    /**
     * @throws ClientException
     */
    public function myOrganization(): Organization
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            return $user->organizations()->firstOrFail();
        } catch (Throwable $ex) {
            throw new ClientException(Exceptions::CLIENT_USER_NOT_IN_ORGANIZATION);
        }
    }

    /**
     * @throws ClientException
     */
    public function myOrganizationEdit(OrganizationPublicUpdateDto $dto): Organization
    {
        // TODO dodać consents
        return $this->organizationRepository->myUpdate($this->myOrganization(), $dto);
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    public function organizationUsers(Organization $organization): LengthAwarePaginator
    {
        return $organization->users()->paginate(Config::get('pagination.per_page'));
    }

    private function sendNewOrganizationAlert(Organization $organization): void
    {
        $admins = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereHas('permissions', fn (Builder $query) => $query->where('name', '=', 'organizations.edit')))
            ->whereHas('preferences', fn (Builder $query) => $query->where('new_organization_alert', '=', true));

        $admins->each(fn (User $admin) => Mail::to($admin->email)->locale('pl')->send(new OrganizationRegistered($organization)));
    }
}
