<?php

declare(strict_types=1);

namespace Domain\Organization\Listeners;

use Domain\Organization\Events\OrganizationAccepted;
use Domain\Organization\Notifications\OrganizationAccepted as OrganizationAcceptedNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OrganizationAcceptedListener
{
    public function handle(OrganizationAccepted $event): void
    {
        $organization = $event->getOrganization();

        try {
            $organization->notify(new OrganizationAcceptedNotification($organization, $event->getToken(), $event->getRedirectUrl()));
        } catch (Throwable) {
            Log::error("Couldn't send organization accepted information to the address: {$organization->email}");
        }
    }
}
