<?php

use App\Models\SavedAddress;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SavedAddress::where('type', 0)
            ->chunk(100, fn ($savedAddresses) => $savedAddresses->each(function ($savedAddress): void {
                if (
                    SavedAddress::where('type', 1)
                        ->where('address_id', $savedAddress->address_id)
                        ->where('user_id', $savedAddress->user_id)
                        ->first() === null
                ) {
                    SavedAddress::create(
                        [
                            'type' => 1,
                            'address_id' => $savedAddress->address_id,
                            'user_id' => $savedAddress->user_id,
                            'default' => $savedAddress->default,
                            'name' => $savedAddress->name,
                        ],
                    );
                }
            }));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SavedAddress::where('type', 1)
            ->chunk(100, fn ($savedAddresses) => $savedAddresses->each(function ($savedAddress): void {
                if (
                    SavedAddress::where('type', 0)
                        ->where('address_id', $savedAddress->address_id)
                        ->where('user_id', $savedAddress->user_id)
                        ->first() !== null
                ) {
                    $savedAddress->delete();
                }
            }));
    }
};
