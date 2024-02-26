<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $this->updateTypeValue('orders', 'buyer_type', 'App\Models\User', 'User');
        $this->updateTypeValue('orders', 'buyer_type', 'Domain\App\Models\App', 'App');
    }

    public function down(): void
    {
        $this->updateTypeValue('orders', 'buyer_type', 'User', 'App\Models\User');
        $this->updateTypeValue('orders', 'buyer_type', 'App', 'Domain\App\Models\App');
    }

    private function updateTypeValue(
        string $table,
        string $column,
        string $oldValue,
        string $newValue,
    ): void {
        DB::table($table)->where($column, '=', $oldValue)->update([$column => $newValue]);
    }
};
