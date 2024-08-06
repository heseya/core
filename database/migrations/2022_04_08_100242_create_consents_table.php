<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description_html');
            $table->boolean('required');
            $table->timestamps();
        });

        Schema::create('consent_user', function (Blueprint $table): void {
            $table->foreignUuid('consent_id')->constrained('consents')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('value');
        });

        Permission::create(['name' => 'consents.show', 'display_name' => 'Dostęp do listy zgód']);
        Permission::create(['name' => 'consents.add', 'display_name' => 'Możliwość tworzenia zgód']);
        Permission::create(['name' => 'consents.edit', 'display_name' => 'Możliwość edycji zgód']);
        Permission::create(['name' => 'consents.remove', 'display_name' => 'Możliwość usuwania zgód']);

        Role::where('type', RoleType::AUTHENTICATED->value)->firstOrFail()->givePermissionTo('consents.show');
        Role::where('type', RoleType::UNAUTHENTICATED->value)->firstOrFail()->givePermissionTo('consents.show');
        Role::where('type', RoleType::OWNER->value)->firstOrFail()->givePermissionTo([
            'consents.show',
            'consents.add',
            'consents.edit',
            'consents.remove',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_user');
        Schema::dropIfExists('consents');
    }
};
