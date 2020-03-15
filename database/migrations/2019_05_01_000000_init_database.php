<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InitDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('fb', 256)->nullable();
            $table->string('fb_page', 256)->nullable();
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 16);
            $table->tinyInteger('value')->unsigned();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 80);
            $table->string('slug', 80)->unique()->index();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->json('name');
            $table->string('slug', 80)->unique()->index();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->json('name');
            $table->string('symbol', 128)->index();
            $table->integer('qty')->unsigned()->default(0);
            $table->smallInteger('category_id')->index()->unsigned()->nullable();
            $table->integer('photo_id')->unsigned()->index()->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('photo_id')->references('id')->on('photos')->onDelete('set null');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->json('name');
            $table->string('slug')->unique()->index();
            $table->float('price', 19, 2);
            $table->tinyInteger('tax_id')->index()->unsigned();
            $table->smallInteger('brand_id')->index()->unsigned();
            $table->smallInteger('category_id')->index()->unsigned();
            $table->json('description')->nullable();
            $table->boolean('public')->default(false);
            $table->timestamps();

            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict');
        });

        Schema::create('product_gallery', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('product_id')->unsigned()->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->morphs('media');
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('zip', 16)->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 16)->unique();
            $table->integer('client_id')->unsigned()->index()->nullable();
            $table->string('email', 256);
            $table->tinyInteger('payment_status')->default(0);
            $table->tinyInteger('shop_status')->default(0);
            $table->smallInteger('delivery_method')->nullable();
            $table->tinyInteger('delivery_status')->default(0);
            $table->string('delivery_tracking')->nullable();
            $table->integer('delivery_address')->unsigned()->index()->nullable();
            $table->integer('invoice_address')->unsigned()->index()->nullable();
            $table->timestamps();

            // Relations
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('delivery_address')->references('id')->on('addresses')->onDelete('restrict');
            $table->foreign('invoice_address')->references('id')->on('addresses')->onDelete('restrict');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('order_id')->unsigned()->index();
            $table->string('external_id')->index()->nullable();
            $table->string('method', 16);
            $table->string('status', 32)->nullable();
            $table->string('currency', 3);
            $table->float('amount', 19, 2);
            $table->string('url', 1000)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
        });

        Schema::create('product_schemas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned()->index();
            $table->string('name');
            $table->boolean('required')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('product_schema_item', function (Blueprint $table) {
            $table->increments('id');
            $table->float('extra_price', 19, 2);

            $table->integer('product_schema_id')->unsigned()->index();
            $table->foreign('product_schema_id')->references('id')->on('product_schemas')->onDelete('cascade');

            $table->integer('item_id')->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });

        Schema::create('order_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('order_id')->unsigned()->index();
            $table->string('content');
            $table->string('user');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id')->unsigned()->nullable()->index();
            $table->smallInteger('system')->default(0);
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('received')->default(false);
            $table->text('content');
            $table->string('external_id')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('chat_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('chat_id')->references('id')->on('chats');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('symbol')->nullable()->index();
            $table->float('qty', 8, 4);
            $table->float('price', 19, 2);
            $table->nestedSet();
            $table->timestamps();
        });

        Schema::create('order_order_item', function (Blueprint $table) {
            $table->integer('order_id')->unsigned()->index();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->bigInteger('order_item_id')->unsigned()->index();
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 128)->unique()->index();
            $table->boolean('public')->default(false);
            $table->json('name');
            $table->json('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('items');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_gallery');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('product_schemas');
        Schema::dropIfExists('product_schema_item');
        Schema::dropIfExists('chats');
        Schema::dropIfExists('order_logs');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_order_item');
        Schema::dropIfExists('pages');
    }
}
