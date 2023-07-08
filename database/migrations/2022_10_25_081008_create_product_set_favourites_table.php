<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('favourite_product_sets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('user');
            $table->foreignUuid('product_set_id')->references('id')->on('product_sets');
            $table->softDeletes();
            $table->timestamps();
        });

        Permission::create(['name' => 'profile.favourites_manage', 'display_name' => 'Możliwość zarządzania swoją listą ulubionych']);

        $authenticated = Role::where('type', '=', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->givePermissionTo('profile.favourites_manage');
        $authenticated->save();

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo('profile.favourites_manage');
        $owner->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('favourite_product_set');

        $authenticated = Role::where('type', '=', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->revokePermissionTo('profile.favourites_manage');
        $authenticated->save();

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->revokePermissionTo('profile.favourites_manage');
        $owner->save();

        Permission::where('name', '=', 'profile.favourites_manage')->delete();
    }
};
