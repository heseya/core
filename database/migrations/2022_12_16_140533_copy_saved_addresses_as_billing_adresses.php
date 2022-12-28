<?php

use App\Models\SavedAddress;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SavedAddress::where('type', 0)
            ->chunk(100, fn ($savedAddresses) => $savedAddresses->each(function ($savedAddress) {
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
     *
     * @return void
     */
    public function down()
    {
        SavedAddress::where('type', 1)
            ->chunk(100, fn ($savedAddresses) => $savedAddresses->each(function ($savedAddress) {
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
