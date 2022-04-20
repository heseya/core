<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAppsTable extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('key');
            $table->string('name')->nullable(false)->change();
            $table->string('microfrontend_url')->nullable();
            $table->string('slug');
            $table->string('version');
            $table->string('api_version');
            $table->string('licence_key')->nullable();
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('author')->nullable();
            $table->string('uninstall_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->string('key');
            $table->string('name')->nullable()->change();
            $table->dropColumn('microfrontend_url');
            $table->dropColumn('slug');
            $table->dropColumn('version');
            $table->dropColumn('licence_key');
            $table->dropColumn('description');
            $table->dropColumn('icon');
            $table->dropColumn('author');
            $table->dropColumn('uninstall_token');
        });
    }
}
