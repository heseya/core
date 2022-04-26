<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditsTable extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('user_type')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('event');
            $table->string('auditable_type');
            $table->uuid('auditable_id');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'user_type']);
            $table->index(['auditable_id', 'auditable_type']);
        });
    }

    public function down(): void
    {
        Schema::drop('audits');
    }
}
