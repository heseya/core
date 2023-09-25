<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Role::query()->where('name', 'Unauthenticated')->first()->revokePermissionTo(
            'auth.password_change',
        );
    }

    public function down(): void
    {
        Role::query()->where('name', 'Unauthenticated')->first()->givePermissionTo(
            'auth.password_change',
        );
    }
};
