<?php

declare(strict_types=1);

namespace Domain\Organization\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use Domain\Organization\Dtos\OrganizationAcceptDto;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Enums\OrganizationStatus;
use Domain\Organization\Events\OrganizationAccepted;
use Domain\Organization\Events\OrganizationRejected;
use Domain\Organization\Models\Organization;
use Domain\Organization\Models\OrganizationToken;
use Domain\Organization\Repositories\OrganizationRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
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
        return $this->organizationRepository->create($dto);
    }

    public function update(string $id, OrganizationUpdateDto $dto): Organization
    {
        return $this->organizationRepository->update($id, $dto);
    }

    public function delete(string $id): void
    {
        $this->organizationRepository->delete($id);
    }

    /**
     * @throws ClientException
     */
    public function accept(Organization $organization, OrganizationAcceptDto $dto): Organization
    {
        $organization = $this->updateStatus($organization, OrganizationStatus::VERIFIED);

        // TODO send mail with invitation
        //  jakiś token do maila, albo sam token, żeby można było później od razu zweryfikować
        //  tu trzeba dodawać link do maila, żeby kierował na właściwą stronę (może niech sie po prostu przekazuje w requeście do akceptacji ??)
        // OrganizationToken z email z Organization
        $token = Str::random(128);
        $organization->tokens()->save(OrganizationToken::make([
            'email' => $organization->email,
            'token' => $token,
            'expires_at' => Carbon::now()->addSeconds(Config::get('organization.token_expires_time')),
        ]));

        OrganizationAccepted::dispatch($organization, $dto->redirect_url, $token);

        return $organization;
    }

    /**
     * @throws ClientException
     */
    public function reject(Organization $organization): Organization
    {
        $organization = $this->updateStatus($organization, OrganizationStatus::REJECTED);

        OrganizationRejected::dispatch($organization);

        return $organization;
    }

    /**
     * @throws ClientException
     */
    private function updateStatus(Organization $organization, OrganizationStatus $status): Organization
    {
        if ($organization->status === OrganizationStatus::VERIFIED && $organization->status !== $status) {
            throw new ClientException(Exceptions::CLIENT_ORGANIZATION_VERIFIED);
        }
        if ($organization->status === $status) {
            throw new ClientException(Exceptions::CLIENT_ORGANIZATION_SAME_STATUS);
        }

        return $this->organizationRepository->updateStatus($organization, $status);
    }
}
