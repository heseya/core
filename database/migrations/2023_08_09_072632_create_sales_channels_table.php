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
        Schema::create('sales_channels', function (Blueprint $table) {
            $table->uuid();
            $table->text('name')->nullable()->change();
            $table->string('slug', 32);
            $table->enum('status', Status::cases());
            $table->uuid('default_currency_id');
            $table->uuid('default_language_id');
            $table->timestamps();

            // TODO: remove temp field
            $table->string('vat_rate');
        });

        SalesChannel::query()->create([
            'name' => 'Domyślny',
            'slug' => 'pl',
            'status' => Status::ACTIVE,
            'default_currency_id' => Currency::DEFAULT,
            'default_language_id' => Language::default(),
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
