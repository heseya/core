<?php

use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('type')->default(RoleType::REGULAR->value)->change();
            $roles = Role::all();
            $roles->each(function (Role $role) {
                $type = match ($role->type) {
                    '0' => RoleType::REGULAR,
                    '1' => RoleType::OWNER,
                    '2' => RoleType::UNAUTHENTICATED,
                    '3' => RoleType::AUTHENTICATED,
                    default => null,
                };
                if ($type !== null) {
                    $role->type = $type;
                    $role->save();
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
