<?php

namespace Domain\Organization\Listeners;

use Domain\Organization\Events\OrganizationRejected;
use Domain\Organization\Notifications\OrganizationRejected as OrganizationRejectNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrganizationRejectedListener
{
    public function handle(OrganizationRejected $event): void
    {
        $organization = $event->getOrganization();

        try {
            $organization->notify(new OrganizationRejectNotification($organization));
        } catch (Throwable) {
            Log::error("Couldn't send organization reject information to the address: {$organization->email}");
        }
    }
}
