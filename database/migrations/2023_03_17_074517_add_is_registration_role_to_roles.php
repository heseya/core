<?php

use App\Enums\RoleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('is_registration_role')->default(false);
        });

        DB::table('roles')
            ->where('type', RoleType::AUTHENTICATED)
            ->update(['is_registration_role' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('is_registration_role');
        });
    }
};
