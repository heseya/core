<?php

use Domain\Consent\Enums\ConsentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->string('type')->default(ConsentType::USER);
        });

        Schema::create('consent_organization', function (Blueprint $table): void {
            $table->foreignUuid('consent_id')->constrained('consents')->onDelete('cascade');
            $table->foreignUuid('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->boolean('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::dropIfExists('consent_organization');
    }
};
