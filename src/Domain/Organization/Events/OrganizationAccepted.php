<?php

namespace Domain\Organization\Events;

use Domain\Organization\Models\Organization;

final class OrganizationAccepted extends OrganizationEvent
{
    public function __construct(
        Organization $organization,
        private readonly string $redirect_url,
        private readonly string $token
    ) {
        parent::__construct($organization);
    }

    public function getRedirectUrl(): string
    {
        return $this->redirect_url;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
