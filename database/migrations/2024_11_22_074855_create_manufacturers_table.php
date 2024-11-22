<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->foreignUuid('address_id')->nullable()->references('id')->on('addresses')->onDelete('restrict');
            $table->timestamps();
        });

        Permission::create(['name' => 'manufacturers.show', 'display_name' => 'Dostęp do listy producentów']);
        Permission::create(['name' => 'manufacturers.show_details', 'display_name' => 'Dostęp do szczegółów producenta']);
        Permission::create(['name' => 'manufacturers.add', 'display_name' => 'Możliwość tworzenia producenta']);
        Permission::create(['name' => 'manufacturers.edit', 'display_name' => 'Możliwość edycji producenta']);
        Permission::create(['name' => 'manufacturers.remove', 'display_name' => 'Możliwość usuwania producentów']);

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->givePermissionTo([
            'manufacturers.show',
            'manufacturers.show_details',
            'manufacturers.add',
            'manufacturers.edit',
            'manufacturers.remove',
        ]);
        $owner->save();
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturers');

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->revokePermissionTo([
            'manufacturers.show',
            'manufacturers.show_details',
            'manufacturers.add',
            'manufacturers.edit',
            'manufacturers.remove',
        ]);
        $owner->save();

        Permission::findByName('manufacturers.show')->delete();
        Permission::findByName('manufacturers.show_details')->delete();
        Permission::findByName('manufacturers.add')->delete();
        Permission::findByName('manufacturers.edit')->delete();
        Permission::findByName('manufacturers.remove')->delete();
    }
};
