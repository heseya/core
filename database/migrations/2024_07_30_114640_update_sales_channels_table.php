<?php

use Domain\PaymentMethods\Models\PaymentMethod;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Support\Enum\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_channels', function (Blueprint $table) {
            $table->string('activity')->default(SalesChannelActivityType::ACTIVE);
            $table->boolean('default')->default(false);

            $table->uuid('price_map_id')->nullable();

            $table->removeColumn('countries_block_list');
            $table->removeColumn('default_currency');

            $table->renameColumn('default_language_id', 'language_id');
        });

        SalesChannel::query()->where('slug', '=', 'default')->update(['default' => true]);

        DB::statement("ALTER TABLE sales_channels MODIFY status ENUM('" . join("', '", array_map(fn ($enum) => $enum->value, SalesChannelStatus::cases())) . "', '" . join("', '", array_map(fn ($enum) => $enum->value, Status::cases())) . "') DEFAULT '" . SalesChannelStatus::PUBLIC->value . "'");

        SalesChannel::query()->where('status', '=', Status::ACTIVE)->update([
            'status' => SalesChannelStatus::PUBLIC,
        ]);
        SalesChannel::query()->where('status', '=', Status::INACTIVE)->update([
            'status' => SalesChannelStatus::PRIVATE,
        ]);
        SalesChannel::query()->where('status', '=', Status::HIDDEN)->update([
            'status' => SalesChannelStatus::PUBLIC,
        ]);

        DB::statement("ALTER TABLE sales_channels MODIFY status ENUM('" . join("', '", array_map(fn ($enum) => $enum->value, SalesChannelStatus::cases())) . "') DEFAULT '" . SalesChannelStatus::PUBLIC->value . "'");

        Schema::create('sales_channel_shipping_method', function (Blueprint $table) {
            $table->foreignUuid('sales_channel_id')->references('id')->on('sales_channels')->onDelete('cascade');
            $table->foreignUuid('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('cascade');
        });

        Schema::create('sales_channel_payment_method', function (Blueprint $table) {
            $table->foreignUuid('sales_channel_id')->references('id')->on('sales_channels')->onDelete('cascade');
            $table->foreignUuid('payment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
        });

        $shippingMethods = ShippingMethod::query()->pluck('id');
        $paymentMethods = PaymentMethod::query()->pluck('id');

        /** @var SalesChannel $channel */
        foreach (SalesChannel::query()->get() as $channel) {
            $channel->shippingMethods()->attach($shippingMethods);
            $channel->paymentMethods()->attach($paymentMethods);
        }

        Schema::dropIfExists('sales_channels_countries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE sales_channels MODIFY status ENUM('" . join("', '", array_map(fn ($enum) => $enum->value, SalesChannelStatus::cases())) . "', '" . join("', '", array_map(fn ($enum) => $enum->value, Status::cases())) . "') DEFAULT '" . SalesChannelStatus::PUBLIC->value . "'");

        SalesChannel::query()->where('status', '=', SalesChannelStatus::PUBLIC)->update([
            'status' => Status::ACTIVE,
        ]);
        SalesChannel::query()->where('status', '=', SalesChannelStatus::PRIVATE)->update([
            'status' => Status::INACTIVE,
        ]);

        DB::statement("ALTER TABLE sales_channels MODIFY status ENUM('" . join("', '", array_map(fn ($enum) => $enum->value, Status::cases())) . "') DEFAULT '" . SalesChannelStatus::PUBLIC->value . "'");

        Schema::table('sales_channels', function (Blueprint $table) {
            $table->dropColumn('activity');
            $table->dropColumn('default');
            $table->dropColumn('price_map_id');
        });

        Schema::dropIfExists('sales_channel_shipping_method');
        Schema::dropIfExists('sales_channel_payment_method');

        Schema::create('sales_channels_countries', function (Blueprint $table) {
            $table->uuid('sales_channel_id')->index();
            $table->string('country_code', 2)->index();

            $table->primary(['sales_channel_id', 'country_code']);
        });
    }
};
