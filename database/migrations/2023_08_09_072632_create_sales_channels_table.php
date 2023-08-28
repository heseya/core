<?php

use App\Enums\RoleType;
use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
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
            $table->uuid('id')->primary()->index();
            $table->text('name');
            $table->string('vat_rate', 9);
            $table->string('slug', 32);
            $table->enum('status', array_map(fn ($enum) => $enum->value, Status::cases()));
            $table->boolean('countries_block_list');
            $table->string('default_currency', 9);
            $table->uuid('default_language_id');
            $table->timestamps();
        });

        Schema::create('sales_channels_countries', function (Blueprint $table) {
            $table->uuid('sales_channel_id')->index();
            $table->string('country_code', 2)->index();

            $table->primary(['sales_channel_id', 'country_code']);
        });

        /** @var SalesChannel $channel */
        $channel = SalesChannel::query()->make([
            'slug' => 'default',
            'status' => Status::ACTIVE->value,
            'countries_block_list' => true,
            'default_currency' => Currency::DEFAULT,
            'default_language_id' => Language::default()?->getKey(),
            'vat_rate' => '0',
        ]);

        foreach (Language::query()->get() as $language) {
            $channel->setLocale($language->getKey())->fill([
                'name' => 'Default',
            ]);
        }
        $channel->save();

        Permission::create([
            'name' => 'sales_channels.show_hidden',
            'display_name' => 'Dostęp do nieaktywnych kanałów sprzedaży',
        ]);
        Permission::create([
            'name' => 'sales_channels.add',
            'display_name' => 'Możliwość dodawania kanałów sprzedaży',
        ]);
        Permission::create([
            'name' => 'sales_channels.edit',
            'display_name' => 'Możliwość aktualizacji kanałów sprzedaży',
        ]);
        Permission::create([
            'name' => 'sales_channels.remove',
            'display_name' => 'Możliwość usuwania kanałów sprzedaży',
        ]);

        /** @var Role $owner */
        $owner = Role::query()
            ->where('type', RoleType::OWNER->value)
            ->firstOrFail();

        $owner->givePermissionTo(
            'sales_channels.show_hidden',
            'sales_channels.add',
            'sales_channels.edit',
            'sales_channels.remove',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_channels');
    }
};
