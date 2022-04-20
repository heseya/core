<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHiddenAndNoNotificationsToStatusesTable extends Migration
{
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->boolean('hidden')->default(false);
            $table->boolean('no_notifications')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->dropColumn('hidden');
            $table->dropColumn('no_notifications');
        });
    }
}
