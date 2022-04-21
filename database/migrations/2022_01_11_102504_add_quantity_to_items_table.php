<?php

use App\Models\Item;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantityToItemsTable extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->decimal('quantity', 16, 4)->default(0);
        });

        Item::chunk(100, fn ($item) => $item->each(
            fn (Item $item) => $item->update([
                'quantity' => $item->deposits->sum('quantity'),
            ])
        ));
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('quantity');
        });
    }
}
