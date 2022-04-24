<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InitDatabase extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
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

        Schema::create('password_resets', function (Blueprint $table): void {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

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

        Schema::create('items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sku')->index()->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('deposits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->float('quantity', 8, 4);
            $table->uuid('item_id')->index();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->float('price', 19, 4);
            $table->uuid('brand_id')->index()->nullable();
            $table->uuid('category_id')->index()->nullable();
            $table->text('description_md')->nullable();
            $table->boolean('public')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict');
        });

        Schema::create('schemas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedTinyInteger('type')->default(0);
            $table->boolean('required')->default(false);
            $table->boolean('hidden')->default(false);
            $table->string('name');
            $table->string('description')->nullable();
            $table->float('price', 19, 4)->default(0);
            $table->string('min')->nullable();
            $table->string('max')->nullable();
            $table->float('step', 8, 8)->nullable();
            $table->string('default')->nullable();
            $table->string('pattern')->nullable();
            $table->string('validation')->nullable();
            $table->timestamps();
        });

        Schema::create('options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->float('price', 19, 4)->default(0);
            $table->boolean('disabled')->default(false);
            $table->uuid('schema_id')->index();
            $table->timestamps();

            $table->foreign('schema_id')->references('id')->on('schemas')->onDelete('cascade');
        });

        Schema::create('option_items', function (Blueprint $table): void {
            $table->uuid('option_id')->index();
            $table->uuid('item_id')->index();

            $table->primary(['option_id', 'item_id']);

            $table->foreign('option_id')->references('id')->on('options')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });

        Schema::create('product_schemas', function (Blueprint $table): void {
            $table->uuid('product_id')->index();
            $table->uuid('schema_id')->index();

            $table->primary(['product_id', 'schema_id']);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('schema_id')->references('id')->on('schemas')->onDelete('cascade');
        });

        Schema::create('media', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->tinyInteger('type');
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('product_media', function (Blueprint $table): void {
            $table->uuid('media_id')->index();
            $table->uuid('product_id')->index();

            $table->primary(['media_id', 'product_id']);

            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('alias');
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('shipping_method_payment_method', function (Blueprint $table): void {
            $table->uuid('shipping_method_id')->index();
            $table->uuid('payment_method_id')->index();

            $table->primary(['shipping_method_id', 'payment_method_id'], 'shipping_method_payment_method_primary');

            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('vat', 15)->nullable();
            $table->string('zip', 16)->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 60);
            $table->string('color', 8);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 16)->unique();
            $table->string('email');
            $table->string('currency', 3);
            $table->string('comment', 1000)->nullable();
            $table->string('shipping_number')->nullable();
            $table->float('shipping_price', 19, 4);
            $table->uuid('status_id')->nullable();
            $table->uuid('shipping_method_id')->nullable();
            $table->uuid('delivery_address_id')->index()->nullable();
            $table->uuid('invoice_address_id')->index()->nullable();
            $table->timestamps();

            // Relations
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('set null');
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('restrict');
            $table->foreign('delivery_address_id')->references('id')->on('addresses')->onDelete('restrict');
            $table->foreign('invoice_address_id')->references('id')->on('addresses')->onDelete('restrict');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->string('external_id')->index()->nullable();
            $table->string('method', 16);
            $table->boolean('payed')->default(false);
            $table->float('amount', 19, 4);
            $table->string('redirect_url', 1000)->nullable();
            $table->string('continue_url', 1000)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
        });

        Schema::create('order_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->string('content');
            $table->string('user');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
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

        Schema::create('order_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->float('quantity', 8, 4);
            $table->float('price', 19, 4);
            $table->uuid('order_id')->index();
            $table->uuid('product_id')->index();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });

        Schema::create('order_schemas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('value');
            $table->float('price', 19, 4);
            $table->uuid('order_product_id')->index();
            $table->timestamps();

            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade');
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique()->index();
            $table->boolean('public')->default(false);
            $table->string('name');
            $table->text('content_md');
            $table->timestamps();
        });

        Schema::create('package_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->float('weight');
            $table->integer('width');
            $table->integer('height');
            $table->integer('depth');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique()->index();
            $table->string('value');
            $table->boolean('public');
            $table->timestamps();
        });
    }
}
