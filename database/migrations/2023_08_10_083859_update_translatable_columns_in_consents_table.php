<?php

use Domain\Consent\Models\Consent;
use Domain\Language\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Support\Utils\Migrate;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('published')->nullable();
        });

        $lang = Language::query()->where('default', true)->value('id');

        Consent::query()->update([
            'name' => Migrate::lang('name', $lang),
            'published' => [$lang],
        ]);
    }

    public function down(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->string('name')->change();
            $table->dropColumn('published');
        });
    }
};
