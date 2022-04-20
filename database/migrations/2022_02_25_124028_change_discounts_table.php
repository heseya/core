<?php

use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDiscountsTable extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('name');
            $table->integer('priority');
            $table->string('target_type');
            $table->boolean('target_is_allow_list');

            $table->renameColumn('discount', 'value');

            $table->string('code')->nullable()->change();
            $table->string('type')->change();
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->string('type')->change();
        });

        Schema::create('discount_condition_groups', function (Blueprint $table) {
            $table->uuid('discount_id')->index();
            $table->uuid('condition_group_id')->index();

            $table->primary(['discount_id', 'condition_group_id']);

            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
            $table->foreign('condition_group_id')->references('id')->on('condition_groups')->onDelete('cascade');
        });

        Schema::create('model_has_discounts', function (Blueprint $table) {
            $table->uuid('discount_id');

            $table->string('model_type');

            $table->uuid('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_discounts_model_id_model_type_index');

            $table->foreign('discount_id')
                ->references('id')
                ->on('discounts')
                ->onDelete('cascade');

            $table->primary(['discount_id', 'model_id', 'model_type'], 'model_has_discounts_primary');
        });

        Discount::chunk(100, fn ($discount) => $discount->each(
            function (Discount $discount) {
                $conditionGroup = ConditionGroup::create();

                $conditionGroup->conditions()->create([
                    'type' => ConditionType::MAX_USES,
                    'value' => [
                        'max_uses' => $discount->max_uses,
                    ]
                ]);

                if ($discount->starts_at !== null || $discount->expires_at !== null) {
                    $conditionGroup->conditions()->create([
                        'type' => ConditionType::DATE_BETWEEN,
                        'value' => [
                            'start_at' => $discount->starts_at,
                            'end_at' => $discount->expires_at,
                        ]
                    ]);
                }

                $discount->conditionGroups()->attach($conditionGroup);
            }
        ));

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('max_uses');
            $table->dropColumn('starts_at');
            $table->dropColumn('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->unsignedInteger('max_uses')->default(1);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
        });

        Discount::chunk(100, fn ($discount) => $discount->each(
            function (Discount $discount) {
                $conditionGroups = $discount->conditionGroups()->first();

                $maxUsesCondition = $conditionGroups->conditions()->where('type', ConditionType::MAX_USES)->first();
                $maxUses = 1;
                if ($maxUsesCondition !== null) {
                    $maxUses = $maxUsesCondition->value['max_uses'];
                }

                $dateBetween = $conditionGroups->conditions()->where('type', ConditionType::DATE_BETWEEN)->first();
                $startAt = null;
                $expiresAt = null;
                if ($dateBetween !== null) {
                    $startAt = $dateBetween->value['start_at'] ?? null;
                    $expiresAt = $dateBetween->value['end_at'] ?? null;
                }

                $discount->update([
                    'max_uses' => $maxUses,
                    'starts_at' => $startAt,
                    'expires_at' => $expiresAt,
                ]);
                $discount->conditionGroups()->delete();
            }
        ));

        Schema::dropIfExists('model_has_discounts');
        Schema::dropIfExists('discount_condition_groups');

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->unsignedInteger('type')->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('priority');
            $table->dropColumn('target_type');
            $table->dropColumn('target_is_allow_list');

            $table->renameColumn('value', 'discount');

            $table->string('code')->change();
            $table->unsignedInteger('type')->default(1)->change();
        });
    }
}
