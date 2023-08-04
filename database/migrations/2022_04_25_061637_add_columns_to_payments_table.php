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
        Permission::create(['name' => 'payments.show', 'display_name' => 'Dostęp do listy transakcji']);
        Permission::create(['name' => 'payments.show_details', 'display_name' => 'Dostęp do szczegółów transakcji']);

        $owner = Role::where('type', RoleType::OWNER->value)->first();

        $owner->givePermissionTo([
            'payments.show',
            'payments.show_details',
        ]);
        $owner->save();

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('status')->nullable();
            $table->foreignUuid('method_id')->nullable()->references('id')->on('payment_methods');
            $table->string('method')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $owner = Role::where('type', RoleType::OWNER->value)->first();
        $owner->revokePermissionTo([
            'payments.show',
            'payments.show_details',
        ]);
        $owner->save();

        Permission::findByName('payments.show')->delete();
        Permission::findByName('payments.show_details')->delete();

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('status');
            $table->dropColumn('method_id');
        });
    }
};
