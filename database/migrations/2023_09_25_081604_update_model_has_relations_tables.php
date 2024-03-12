<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $tables = $this->getTables();

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column => $value) {
                foreach ($value['relations'] as $oldValue => $newValue) {
                    $this
                        ->updateTypeValue(
                            $table,
                            $column,
                            $oldValue,
                            $newValue,
                            $value['id_column'],
                            $value['id_second_column'],
                        );
                }
            }
        }
    }

    public function down(): void
    {
        $tables = $this->getTables();

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column => $value) {
                foreach ($value['relations'] as $oldValue => $newValue) {
                    $this
                        ->updateTypeValue(
                            $table,
                            $column,
                            $newValue,
                            $oldValue,
                            $value['id_column'],
                            $value['id_second_column'],
                        );
                }
            }
        }
    }

    private function getTables(): array
    {
        return [
            'model_has_roles' => [
                'model_type' => [
                    'id_column' => 'role_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\User' => 'User',
                        'App\Models\App' => 'App',
                    ],
                ],
            ],
            'model_has_discounts' => [
                'model_type' => [
                    'id_column' => 'discount_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\Product' => 'Product',
                        'App\Models\ProductSet' => 'ProductSet',
                        'Domain\ProductSet\ProductSet' => 'ProductSet',
                        'Domain\ShippingMethod\Models\ShippingMethod' => 'ShippingMethod',
                    ],
                ],
            ],
            'order_discounts' => [
                'model_type' => [
                    'id_column' => 'discount_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\Order' => 'Order',
                        'App\Models\OrderProduct' => 'OrderProduct',
                    ],
                ],
            ],
            'model_has_permissions' => [
                'model_type' => [
                    'id_column' => 'permission_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\App' => 'App',
                        'App\Models\User' => 'User',
                    ],
                ],
            ],
            'model_has_discount_conditions' => [
                'model_type' => [
                    'id_column' => 'discount_condition_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\Product' => 'Product',
                        'App\Models\ProductSet' => 'ProductSet',
                        'Domain\ProductSet\ProductSet' => 'ProductSet',
                        'App\Models\User' => 'User',
                        'App\Models\Role' => 'Role',
                    ],
                ],
            ],
            'orders' => [
                'model_type' => [
                    'id_column' => 'buyer_type',
                    'id_second_column' => 'buyer_id',
                    'relations' => [
                        'App\Models\User' => 'User',
                        'App\Models\App' => 'App',
                    ],
                ],
            ],
        ];
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
