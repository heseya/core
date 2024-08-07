<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserAgentAndIpToOauthAccessTokensTable extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('oauth_access_tokens')) {
            if (Schema::hasColumn('oauth_access_tokens', 'ip')) {
                Schema::table('oauth_access_tokens', function (Blueprint $table): void {
                    $table->dropColumn('ip');
                });
            }
            if (Schema::hasColumn('oauth_access_tokens', 'user_agent')) {
                Schema::table('oauth_access_tokens', function (Blueprint $table): void {
                    $table->dropColumn('user_agent');
                });
            }
        }
    }
}
