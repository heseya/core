<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::drop('brands');
        Schema::drop('categories');
        Schema::drop('order_notes');
        Schema::drop('oauth_personal_access_clients');
        Schema::drop('oauth_access_tokens');
        Schema::drop('oauth_auth_codes');
        Schema::drop('oauth_refresh_tokens');
        Schema::drop('oauth_clients');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('order_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('message', 1000);
            $table->uuid('order_id')->index();
            $table->uuid('user_id')->index()->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('name');
            $table->string('secret', 100)->nullable();
            $table->string('provider')->nullable();
            $table->text('redirect');
            $table->boolean('personal_access_client');
            $table->boolean('password_client');
            $table->boolean('revoked');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('oauth_personal_access_clients', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('client_id');
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });

        Schema::create('oauth_auth_codes', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->uuid('user_id')->index();
            $table->uuid('client_id');
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
            $table->dateTime('expires_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->uuid('client_id');
            $table->string('name')->nullable();
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
            $table->timestamps();
            $table->dateTime('expires_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });

        Schema::create('oauth_refresh_tokens', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->string('access_token_id', 100)->index();
            $table->boolean('revoked');
            $table->dateTime('expires_at')->nullable();
        });
    }
};
