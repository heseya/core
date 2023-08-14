<?php

use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Support\Enum\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });

        Schema::create('sales_channels', function (Blueprint $table) {
            $table->uuid('id');
            $table->text('name');
            $table->string('slug', 32);
            $table->enum('status', array_map(fn ($enum) => $enum->value, Status::cases()));
            $table->boolean('countries_block_list');
            $table->string('default_currency', 9);
            $table->uuid('default_language_id');
            $table->timestamps();
            $table->string('vat_rate', 9);
        });

        SalesChannel::query()->create([
            'name' => 'Default',
            'slug' => 'default',
            'status' => Status::ACTIVE->value,
            'countries_block_list' => false,
            'default_currency' => Currency::DEFAULT,
            'default_language_id' => Language::default()?->getKey(),
            'vat_rate' => '0',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_channels');
    }
};
