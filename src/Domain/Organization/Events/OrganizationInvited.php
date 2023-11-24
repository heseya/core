<?php

declare(strict_types=1);

namespace Domain\Organization\Events;

use Domain\Organization\Models\OrganizationToken;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrganizationInvited
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        protected OrganizationToken $organizationToken,
        protected string $redirect_url,
        protected string $preferred_locale,
    ) {}

    public function getOrganizationToken(): OrganizationToken
    {
        return $this->organizationToken;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirect_url;
    }

    public function getPreferredLocale(): string
    {
        return $this->preferred_locale;
    }
}
