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
        Schema::create('banners', function (Blueprint $table): void {
            $table->uuid('id')->primary()->index();
            $table->string('slug')->unique()->index();
            $table->string('url');
            $table->string('name');
            $table->boolean('active');
            $table->timestamps();
        });

        Schema::create('responsive_media', function (Blueprint $table): void {
            $table->uuid('id')->primary()->index();
            $table
                ->foreignUuid('banner_id')
                ->references('id')
                ->on('banners')
                ->onDelete('cascade');
            $table->integer('order');
            $table->timestamps();
        });

        Schema::create('media_responsive_media', function (Blueprint $table): void {
            $table
                ->foreignUuid('responsive_media_id')
                ->references('id')
                ->on('responsive_media')
                ->onDelete('cascade');
            $table
                ->foreignUuid('media_id')
                ->references('id')
                ->on('media')
                ->onDelete('cascade');
            $table->integer('min_screen_width');
        });

        Permission::create(['name' => 'banners.show', 'display_name' => 'Możliwość wyświetlania bannerów']);
        Permission::create(['name' => 'banners.add', 'display_name' => 'Możliwość tworzenia bannerów']);
        Permission::create(['name' => 'banners.edit', 'display_name' => 'Możliwość edytowania bannerów']);
        Permission::create(['name' => 'banners.remove', 'display_name' => 'Możliwość usuwania bannerów']);

        Role::where('type', RoleType::AUTHENTICATED)->firstOrFail()->givePermissionTo('banners.show');
        Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail()->givePermissionTo('banners.show');
        Role::where('type', RoleType::OWNER)->firstOrFail()->givePermissionTo([
            'banners.show',
            'banners.add',
            'banners.edit',
            'banners.remove',
        ]);
    }

    public function down(): void
    {
        $permissions = [
            'banners.show',
            'banners.add',
            'banners.edit',
            'banners.remove',
        ];

        Role::where('type', RoleType::AUTHENTICATED)->firstOrFail()->revokePermissionTo('banners.show');
        Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail()->revokePermissionTo('banners.show');
        $owner = Role::query()->where('type', '=', RoleType::OWNER)->firstOrFail();
        $owner->revokePermissionTo($permissions);
        $owner->save();

        foreach ($permissions as $permission) {
            Permission::query()
                ->where('name', '=', $permission)
                ->delete();
        }

        Schema::dropIfExists('media_responsive_media');
        Schema::dropIfExists('responsive_media');
        Schema::dropIfExists('banners');
    }
};
