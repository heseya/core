<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class RemoveOrderLogsAndNotesTables extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('orders_logs');
        Schema::dropIfExists('orders_notes');
    }

    public function down(): void
    {
        //
    }
}
