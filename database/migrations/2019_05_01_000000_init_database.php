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
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('sku')->index()->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->increments('id');
            $table->float('quantity', 8, 4);
            $table->integer('item_id')->index()->unsigned();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->index();
            $table->float('price', 19, 4);
            $table->smallInteger('brand_id')->index()->unsigned();
            $table->smallInteger('category_id')->index()->unsigned();
            $table->integer('user_id')->index()->unsigned()->nullable();
            $table->integer('original_id')->index()->unsigned()->nullable();
            $table->text('description_md')->nullable();
            $table->boolean('digital')->default(false);
            $table->boolean('public')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['slug', 'deleted_at']);

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('type');
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('media_id')->unsigned()->index();
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');

            $table->integer('product_id')->unsigned()->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name');
            $table->float('price', 19, 4);
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('vat', 15)->nullable();
            $table->string('zip', 16)->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 16)->unique();
            $table->string('email');
            $table->smallInteger('shipping_method_id')->unsigned();
            $table->float('shipping_price', 19, 4);
            $table->tinyInteger('payment_status')->default(0);
            $table->tinyInteger('shop_status')->default(0);
            $table->tinyInteger('delivery_status')->default(0);
            $table->string('delivery_tracking')->nullable();
            $table->integer('delivery_address')->unsigned()->index()->nullable();
            $table->integer('invoice_address')->unsigned()->index()->nullable();
            $table->string('comment', 1000)->nullable();
            $table->timestamps();

            // Relations
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('restrict');
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
            $table->float('amount', 19, 4);
            $table->string('redirectUrl', 1000)->nullable();
            $table->string('continueUrl', 1000)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
        });

        Schema::create('product_schemas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('product_id')->unsigned()->index();
            $table->string('name')->nullable();
            $table->integer('type')->unsigned()->default(0);
            $table->boolean('required')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('product_schema_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('extra_price', 19, 4);
            $table->string('value', 256)->nullable();
            $table->integer('item_id')->unsigned()->index()->nullable();
            $table->bigInteger('product_schema_id')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('product_schema_id')->references('id')->on('product_schemas')->onDelete('cascade');
        });

        Schema::create('order_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('order_id')->unsigned()->index();
            $table->string('content');
            $table->string('user');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('message', 1000);
            $table->integer('order_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('quantity', 8, 4);
            $table->float('price', 19, 4);
            $table->integer('order_id')->unsigned()->index();
            $table->integer('product_id')->unsigned()->index();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });

        Schema::create('order_item_product_schema_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('order_item_id')->unsigned()->index();
            $table->bigInteger('product_schema_item_id')->unsigned()->index();

            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('product_schema_item_id')->references('id')->on('product_schema_items')->onDelete('restrict');
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('system')->default(0);
            $table->string('external_id')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('received')->default(false);
            $table->text('content');
            $table->string('external_id')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('chat_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->unique()->index();
            $table->boolean('public')->default(false);
            $table->string('name');
            $table->text('content_md');
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
        return false;
    }
}
