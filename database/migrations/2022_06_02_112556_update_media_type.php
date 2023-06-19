<?php

use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('type')->change();
        });

        Media::query()->where('type', '0')->update([
            'type' => MediaType::OTHER,
        ]);
        Media::query()->where('type', '1')->update([
            'type' => MediaType::PHOTO,
        ]);
        Media::query()->where('type', '2')->update([
            'type' => MediaType::VIDEO,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_jobs');
    }
};
