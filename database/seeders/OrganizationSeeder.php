<?php

namespace Database\Seeders;

use App\Models\Address;
use Domain\Organization\Models\Organization;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SalesChannel $salesChannel */
        $salesChannel = SalesChannel::query()->first();
        Organization::factory()
            ->count(10)
            ->create([
                'sales_channel_id' => $salesChannel->getKey(),
            ])
            ->each(function (Organization $organization) {
                /** @var Address $address */
                $address = Address::factory()->create();
                $address->organizations()->save($organization);
            });
    }
}
