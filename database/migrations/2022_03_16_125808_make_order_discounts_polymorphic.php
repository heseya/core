<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeOrderDiscountsPolymorphic extends Migration
{
    public function up(): void
    {
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->string('model_type');

            $table->dropForeign('order_discounts_order_id_foreign');

            $table->dropPrimary(['order_id', 'discount_id']);

            $table->renameColumn('order_id', 'model_id');

            $table->index(['model_id', 'model_type'], 'model_has_order_discounts_model_id_model_type_index');

            $table->primary(['discount_id', 'model_id', 'model_type'], 'model_has_order_discounts_primary');
        });

        DB::table('order_discounts')->orderBy('model_id')->chunk(100, function ($orderDiscounts) {
            foreach ($orderDiscounts as $orderDiscount) {
                DB::table('order_discounts')->where('model_id', $orderDiscount->model_id)->update([
                    'model_type' => \App\Models\Order::class,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->dropPrimary(['order_id', 'discount_id', 'model_type']);

            $table->dropIndex('model_has_order_discounts_model_id_model_type_index');

            $table->dropColumn('model_type');

            $table->renameColumn('model_id', 'order_id');

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->primary(['order_id', 'discount_id']);
        });
    }
}
