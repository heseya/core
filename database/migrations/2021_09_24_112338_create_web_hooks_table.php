<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebHooksTable extends Migration
{
    public function up(): void
    {
        Schema::create('web_hooks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events');
            $table->boolean('with_issuer');
            $table->boolean('with_hidden');

            $table->uuid('creator_id');
            $table->string('model_type');
            $table->index(['creator_id', 'model_type'], 'web_hooks_creator_id_model_type_index');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('web_hook_event_log_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('web_hook_id')->index();
            $table->dateTime('triggered_at');
            $table->string('url');
            $table->integer('status_code');

            $table->foreign('web_hook_id')->references('id')->on('web_hooks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_hook_event_log_entries');
        Schema::dropIfExists('web_hooks');
    }
}
