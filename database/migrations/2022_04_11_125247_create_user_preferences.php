<?php

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->boolean('successful_login_attempt_alert')->default(false);
            $table->boolean('failed_login_attempt_alert')->default(true);
            $table->boolean('new_localization_login_alert')->default(true);
            $table->boolean('recovery_code_changed_alert')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUuid('preferences_id')->nullable()->constrained('user_preferences');
        });

        foreach (User::all() as $user) {
            $user->preferences()->associate(UserPreference::create());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['preferences_id']);
            $table->dropColumn('preferences_id');
        });

        Schema::dropIfExists('user_preferences');
    }
};
