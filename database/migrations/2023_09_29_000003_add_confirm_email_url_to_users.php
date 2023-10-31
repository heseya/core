<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verify_url')->nullable();
            $table->string('email_verify_token')->nullable();
        });

        User::query()->whereNull('email_verified_at')->update(['email_verified_at' => Carbon::now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('email_verified_at');
            $table->dropColumn('email_verify_url');
            $table->dropColumn('email_verify_token');
        });
    }
};
