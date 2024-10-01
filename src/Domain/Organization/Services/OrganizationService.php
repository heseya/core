<?php

declare(strict_types=1);

namespace Domain\Organization\Services;

use App\DTO\Auth\RegisterDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SavedAddressType;
use App\Events\OrganizationCreated;
use App\Exceptions\ClientException;
use App\Mail\OrganizationRegistered;
use App\Models\SavedAddress;
use App\Models\User;
use Domain\Consent\Enums\ConsentType;
use Domain\Consent\Models\Consent;
use Domain\Consent\Services\ConsentService;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationPublicUpdateDto;
use Domain\Organization\Dtos\OrganizationRegisterDto;
use Domain\Organization\Dtos\OrganizationSavedAddressCreateDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Models\Organization;
use Domain\Organization\Repositories\OrganizationRepository;
use Domain\User\Dtos\UserCreateDto;
use Domain\User\Services\AuthService;
use Domain\User\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OrganizationSavedAddressService $organizationSavedAddressService,
        private AuthService $authService,
        private UserService $userService,
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
        if ($dto->import) {
            $company_name = match (true) {
                is_string($dto->billing_address->company_name) => $dto->billing_address->company_name,
                is_string($dto->billing_address->name) => $dto->billing_address->name,
                default => $organization->getKey(),
            };

            $user = $this->userService->create(UserCreateDto::from([
                'email' => match (true) {
                    is_string($dto->creator_email) => $dto->creator_email,
                    is_string($dto->contact_email) => $dto->contact_email,
                    default => $dto->billing_email,
                },
                'password' => Str::password(),
                'name' => is_string($dto->creator_name) ? $dto->creator_name : ('User imported for ' . $company_name),
            ]), false);

            $organization->users()->attach($user->getKey());

            $consents = Consent::query()
                ->where('type', '=', ConsentType::ORGANIZATION)
                ->where('required', true)
                ->pluck('id', 'id')
                ->map(fn () => true)
                ->toArray();

            if ($organization->address) {
                SavedAddress::create([
                    'name' => $organization->address->name,
                    'user_id' => $user->getKey(),
                    'address_id' => $organization->address->id,
                    'type' => SavedAddressType::BILLING,
                    'default' => true,
                ]);
            }

            $first = true;
            foreach ($organization->deliveryAddresses as $address) {
                SavedAddress::create([
                    'name' => $address->name,
                    'user_id' => $user->getKey(),
                    'address_id' => $address->address_id,
                    'type' => SavedAddressType::SHIPPING,
                    'default' => $first,
                ]);
                $first = false;
            }
        } else {
            $consents = $dto->consents;
        }

        $this->consentService->syncOrganizationConsents($organization, $consents);
        $organization->refresh();

        OrganizationCreated::dispatch($organization);

        return $organization;
    }

    public function update(Organization $organization, OrganizationUpdateDto $dto): Organization
    {
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

            $forceDefault = count($dto->shipping_addresses) === 1;
            /** @var OrganizationSavedAddressCreateDto $shipping_address */
            foreach ($dto->shipping_addresses as $shipping_address) {
                $this->organizationSavedAddressService->storeAddress($shipping_address, SavedAddressType::SHIPPING, $organization->getKey(), $forceDefault);
            }

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
