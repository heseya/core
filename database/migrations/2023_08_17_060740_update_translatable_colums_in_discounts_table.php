<?php

use App\Enums\RoleType;
use App\Models\Discount;
use App\Models\Permission;
use App\Models\Role;
use Domain\Language\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Support\Utils\Migrate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->change();
            $table->text('published')->nullable();
        });

        $lang = Language::query()->where('default', true)->value('id');

        Discount::query()->withTrashed()->update([
            'name' => Migrate::lang('name', $lang),
            'description' => Migrate::lang('description', $lang),
            'published' => [$lang],
        ]);

        Permission::create([
            'name' => 'coupons.show_hidden',
            'display_name' => 'Dostęp do ukrytych kuponów',
        ]);

        Permission::create([
            'name' => 'sales.show_hidden',
            'display_name' => 'Dostęp do ukrytych promocji',
        ]);

        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->givePermissionTo(['coupons.show_hidden', 'sales.show_hidden']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('description')->change();
            $table->dropColumn('published');
        });

        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->revokePermissionTo(['coupons.show_hidden', 'sales.show_hidden']);

        Permission::query()
            ->where('name', 'coupons.show_hidden')
            ->delete();
        Permission::query()
            ->where('name', 'sales.show_hidden')
            ->delete();
    }
};
