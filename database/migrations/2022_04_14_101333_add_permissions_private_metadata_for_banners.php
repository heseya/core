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
        Permission::create([
            'name' => 'banners.show_metadata_private',
            'display_name' => 'Możliwość wyświetlania prywatnych metadanych bannerów'
        ]);

        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->givePermissionTo('banners.show_metadata_private');
    }

    public function down(): void
    {
        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->revokePermissionTo('banners.show_metadata_private');

        Permission::query()
            ->where('name', '=', 'banners.show_metadata_private')
            ->delete();
    }
};
