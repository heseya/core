<?php

use App\Enums\AuthProviderKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_providers', function (Blueprint $table): void {
            $table->uuid('id');
            $table->enum('key', AuthProviderKey::asArray());
            $table->boolean('active');
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
        });

        Schema::create('user_providers', function (Blueprint $table): void {
            $table->uuid('id');
            $table->enum('provider', AuthProviderKey::asArray());
            $table->string('provider_user_id');
            $table->string('user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_providers');
        Schema::dropIfExists('user_providers');

        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->change();
        });
    }
};
