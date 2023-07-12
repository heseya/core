<?php

use App\Enums\RoleType;
use App\Models\Language;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('iso', 16)->unique();
            $table->string('name', 80);
            $table->boolean('default')->default(false);
            $table->boolean('hidden')->default(true);
            $table->timestamps();
        });

        Language::create([
            'iso' => 'pl',
            'name' => 'Polski',
            'default' => true,
            'hidden' => false,
        ]);

        Permission::create(['name' => 'languages.show_hidden', 'display_name' => 'Dostęp do ukrytych języków']);
        Permission::create(['name' => 'languages.add', 'display_name' => 'Możliwość tworzenia języków']);
        Permission::create(['name' => 'languages.edit', 'display_name' => 'Możliwość edycji języków']);
        Permission::create(['name' => 'languages.remove', 'display_name' => 'Możliwość usuwania języków']);

        $owner = Role::where('type', RoleType::OWNER)->first();
        $owner->givePermissionTo([
            'languages.show_hidden',
            'languages.add',
            'languages.edit',
            'languages.remove',
        ]);
        $owner->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
}
