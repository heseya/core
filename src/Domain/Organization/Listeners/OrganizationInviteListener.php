<?php

declare(strict_types=1);

namespace Domain\Organization\Listeners;

use Domain\Organization\Events\OrganizationInvited;
use Domain\Organization\Notifications\OrganizationInvited as OrganizationInvitedNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OrganizationInviteListener
{
    public function handle(OrganizationInvited $event): void
    {
        $organizationToken = $event->getOrganizationToken();
        $redirectUrl = $event->getRedirectUrl();
        $preferredLocale = $event->getPreferredLocale();

        try {
            $organizationToken->notify(new OrganizationInvitedNotification($organizationToken, $redirectUrl, $preferredLocale));
        } catch (Throwable) {
            Log::error("Couldn't send organization invitation information to the address: {$organizationToken->email}");
        }
    }
}
