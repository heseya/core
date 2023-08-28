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
        Schema::table('tags', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('published')->nullable();
        });

        Permission::create([
            'name' => 'tags.show_hidden',
            'display_name' => 'Dostęp do ukrytych tagów',
        ]);

        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->givePermissionTo(['tags.show_hidden']);
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('published');
            $table->string('name', 30)->change();
        });

        Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail()
            ->revokePermissionTo(['tags.show_hidden']);

        Permission::query()
            ->where('name', 'tags.show_hidden')
            ->delete();
    }
};
