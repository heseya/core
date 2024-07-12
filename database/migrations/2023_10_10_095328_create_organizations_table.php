<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary()->index();

            $table->integer('change_version')->default(0);

            $table->string('client_id')->nullable();
            $table->string('billing_email');
            $table->foreignUuid('sales_channel_id')->nullable()->references('id')->on('sales_channels')->onDelete('restrict');
            $table->string('creator_email')->nullable();

            $table->foreignUuid('billing_address_id')->nullable()->references('id')->on('addresses')->onDelete('restrict');

            $table->timestamps();
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->foreignUuid('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('organization_saved_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('default');
            $table->string('name');
            $table->integer('type');
            $table->foreignUuid('address_id')->nullable()->references('id')->on('addresses')->onDelete('cascade');
            $table->foreignUuid('organization_id');
            $table->timestamps();
        });

        Permission::create(['name' => 'organizations.show', 'display_name' => 'Dostęp do listy organizacji']);
        Permission::create(['name' => 'organizations.show_details', 'display_name' => 'Dostęp do szczegółów organizacji']);
        Permission::create(['name' => 'organizations.add', 'display_name' => 'Możliwość tworzenia organizacji']);
        Permission::create(['name' => 'organizations.edit', 'display_name' => 'Możliwość edycji organizacji']);
        Permission::create(['name' => 'organizations.remove', 'display_name' => 'Możliwość usuwania organizacji']);

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->givePermissionTo([
            'organizations.show',
            'organizations.show_details',
            'organizations.add',
            'organizations.edit',
            'organizations.remove',
        ]);
        $owner->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organization_saved_addresses');
        Schema::dropIfExists('organizations');

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->revokePermissionTo([
            'organizations.show',
            'organizations.show_details',
            'organizations.add',
            'organizations.edit',
            'organizations.remove',
        ]);
        $owner->save();

        Permission::findByName('organizations.show')->delete();
        Permission::findByName('organizations.show_details')->delete();
        Permission::findByName('organizations.add')->delete();
        Permission::findByName('organizations.edit')->delete();
        Permission::findByName('organizations.remove')->delete();
    }
};
