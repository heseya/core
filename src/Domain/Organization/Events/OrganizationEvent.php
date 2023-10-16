<?php

declare(strict_types=1);

namespace Domain\Organization\Events;

use Domain\Organization\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class OrganizationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        protected Organization $organization,
    ) {}

    public function getOrganization(): Organization
    {
        return $this->organization;
    }
}
