<?php

namespace App\Events;

use Domain\Organization\Models\Organization;
use Domain\Organization\Resources\OrganizationResource;
use Illuminate\Support\Str;

abstract class OrganizationEvent extends WebHookEvent
{
    protected Organization $organization;

    public function __construct(Organization $organization)
    {
        parent::__construct();
        $this->organization = $organization;
    }

    public function getDataContent(): array
    {
        return OrganizationResource::make($this->organization)->resolve();
    }

    public function getDataType(): string
    {
        return Str::remove('Domain\\Organization\\Models\\', $this->organization::class);
    }
}
