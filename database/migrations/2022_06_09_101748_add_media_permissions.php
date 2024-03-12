<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private $newPermissions = [
        'media.show' => 'Dostęp do listy mediów',
        'media.add' => 'Możliwość dodawania mediów',
        'media.edit' => 'Możliwość edycji mediów',
        'media.remove' => 'Możliwość usuwania mediów',
    ];

    public function up(): void
    {
        foreach ($this->newPermissions as $name => $displayName) {
            Permission::create(['name' => $name, 'display_name' => $displayName]);
        }

        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->givePermissionTo(array_keys($this->newPermissions));
        $owner->save();
    }

    public function down(): void
    {
        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->revokePermissionTo(array_keys($this->newPermissions));
        $owner->save();

        Permission::whereIn('name', array_keys($this->newPermissions))->delete();
    }
};
