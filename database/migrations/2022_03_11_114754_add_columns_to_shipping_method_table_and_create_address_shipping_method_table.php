<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table): void {
            $table->uuid('id')->primary()->change();
        });

        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->string('shipping_type')->default('address');
            $table->string('integration_key')->nullable();
            $table->foreignUuid('app_id')->nullable()->index()->references('id')->on('apps')->onDelete('cascade');
        });

        Schema::create('address_shipping_method', function (Blueprint $table): void {
            $table->uuid('address_id')->index();
            $table->uuid('shipping_method_id')->index();

            $table->primary(['address_id', 'shipping_method_id']);

            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropColumn('shipping_type');
            $table->dropColumn('integration_key');
            $table->dropColumn('app_id');
        });

        Schema::dropIfExists('address_shipping_method');
    }
};
