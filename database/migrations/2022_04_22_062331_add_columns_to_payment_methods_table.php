<?php

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::create([
            'name' => 'payment_methods.show_details',
            'display_name' => 'Dostęp do szczegółów metod płatności',
        ]);

        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->givePermissionTo('payment_methods.show_details');

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->string('icon');
            $table->string('url');
            $table->uuid('app_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('type', RoleType::OWNER)
            ->firstOrFail()
            ->revokePermissionTo('payment_methods.show_details');

        Permission::delete();

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropColumn('icon');
            $table->dropColumn('url');
            $table->dropColumn('app_id');
        });
    }
};
