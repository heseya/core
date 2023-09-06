<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug');
            $table->string('url');
            $table->unsignedSmallInteger('type');
            $table->timestamps();
        });

        Permission::create([
            'name' => 'redirects.show',
            'display_name' => 'Dostęp do listy przekierowań',
        ]);

        Permission::create([
            'name' => 'redirects.add',
            'display_name' => 'Możliwość twożenia przekierowań',
        ]);

        Permission::create([
            'name' => 'redirects.edit',
            'display_name' => 'Możliwość edycji przekierowań',
        ]);

        Permission::create([
            'name' => 'redirects.remove',
            'display_name' => 'Możliwość usuwania przekierowań',
        ]);

        /** @var User $owner */
        $owner = Role::query()->where('type', RoleType::OWNER)->first();

        $owner->givePermissionTo([
            'redirects.show',
            'redirects.add',
            'redirects.edit',
            'redirects.remove',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');

        /** @var User $owner */
        $owner = Role::query()->where('type', RoleType::OWNER)->first();

        $owner->revokePermissionTo([
            'redirects.show',
            'redirects.add',
            'redirects.edit',
            'redirects.remove',
        ]);
    }
};
