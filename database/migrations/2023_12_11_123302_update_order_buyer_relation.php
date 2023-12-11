<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $this->updateTypeValue('orders', 'buyer_type', 'App\Models\User', 'User', 'buyer_type', 'buyer_id');
        $this->updateTypeValue('orders', 'buyer_type', 'App\Models\App', 'App', 'buyer_type', 'buyer_id');
    }

    public function down(): void
    {
        $this->updateTypeValue('orders', 'buyer_type', 'User', 'App\Models\User', 'buyer_type', 'buyer_id');
        $this->updateTypeValue('orders', 'buyer_type', 'App', 'App\Models\App', 'buyer_type', 'buyer_id');
    }

    private function updateTypeValue(
        string $table,
        string $typeColumn,
        string $oldValue,
        string $newValue,
        string $idColumn,
        string $idSecondColumn = null,
    ): void {
        DB::table($table)
            ->orderBy($idColumn)
            ->chunk(100, function ($records) use ($table, $typeColumn, $newValue, $idColumn, $idSecondColumn, $oldValue) {
                foreach ($records as $record) {
                    if ($record->$typeColumn === $oldValue) {
                        $query = DB::table($table)
                            ->where($idColumn, '=', $record->$idColumn);
                        if ($idSecondColumn) {
                            $query->where($idSecondColumn, '=', $record->$idSecondColumn);
                        }
                        $query->update([
                            $typeColumn => $newValue,
                        ]);
                    }
                }
            });
    }
};
