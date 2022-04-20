<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConditionGroupAndDiscountConditionTables extends Migration
{
    public function up(): void
    {
        Schema::create('condition_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });

        Schema::create('discount_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->json('value');

            $table->uuid('condition_group_id');
            $table->foreign('condition_group_id')
                ->references('id')
                ->on('condition_groups')
                ->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('model_has_discount_conditions', function (Blueprint $table) {
            $table->uuid('discount_condition_id');

            $table->string('model_type');

            $table->uuid('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_discount_conditions_model_id_model_type_index');

            $table->foreign('discount_condition_id')
                ->references('id')
                ->on('discount_conditions')
                ->onDelete('cascade');

            $table->primary(['discount_condition_id', 'model_id', 'model_type'], 'model_has_discount_conditions_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_discount_conditions');
        Schema::dropIfExists('discount_conditions');
        Schema::dropIfExists('condition_groups');
    }
}
