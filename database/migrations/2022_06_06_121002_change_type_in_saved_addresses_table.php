<?php

use App\Enums\SavedAddressType;
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
        Schema::table('saved_addresses', function (Blueprint $table) {
            $table->string('type')->change();

            $roles = SavedAddress::all();
            $roles->each(function (SavedAddress $address) {
                $type = match ($address->type) {
                    '0' => SavedAddressType::DELIVERY,
                    '1' => SavedAddressType::INVOICE,
                    default => null,
                };
                if ($type !== null) {
                    $address->type = $type;
                    $address->save();
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saved_addresses', function (Blueprint $table) {
            $table->unsignedTinyInteger('type');
        });
    }
};
