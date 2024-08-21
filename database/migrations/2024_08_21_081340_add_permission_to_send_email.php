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
        Permission::create([
            'name' => 'email.send',
            'display_name' => 'Możliwość wysyłania maili',
            'description' => 'Uprawnienie pozwalające na wysyłanie maili przez dedykowany endpoint',
        ]);

        $owner = Role::where('type', '=', RoleType::OWNER->value)->firstOrFail();
        $owner->givePermissionTo('email.send');
        $owner->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $owner = Role::where('type', '=', RoleType::OWNER->value)->firstOrFail();
        $owner->revokePermissionTo('email.send');
        $owner->save();

        Permission::where('name', '=', 'email.send')->delete();
    }
};
