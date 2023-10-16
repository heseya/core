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
     */
    public function up(): void
    {
        Schema::create('wishlist_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('user_type')->nullable();
            $table->uuid('user_id')->nullable();
            $table->foreignUuid('product_id')->references('id')->on('products');
            $table->softDeletes();
            $table->timestamps();
        });

        Permission::create(['name' => 'profile.wishlist_manage', 'display_name' => 'Możliwość zarządzania swoją listą życzeń']);

        $authenticated = Role::where('type', '=', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->givePermissionTo('profile.wishlist_manage');
        $authenticated->save();

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo('profile.wishlist_manage');
        $owner->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist');

        $authenticated = Role::where('type', '=', RoleType::AUTHENTICATED)->firstOrFail();
        $authenticated->revokePermissionTo('profile.wishlist_manage');
        $authenticated->save();

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->revokePermissionTo('profile.wishlist_manage');
        $owner->save();

        Permission::where('name', '=', 'profile.wishlist_manage')->delete();
    }
};
