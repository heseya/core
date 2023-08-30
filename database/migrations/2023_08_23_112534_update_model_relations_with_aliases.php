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
                        ->updateTypeValue($table, $column, $oldValue, $newValue, $value['id_column'], $value['id_second_column']);
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
                        ->updateTypeValue($table, $column, $newValue, $oldValue, $value['id_column'], $value['id_second_column']);
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
                        'App\Models\ShippingMethod' => 'ShippingMethod',
                    ],
                ]
            ],
            'wishlist_products' => [
                'user_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => 'User',
                        'App\Models\App' => 'App',
                    ],
                ]
            ],
            'web_hooks' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => 'User',
                        'App\Models\App' => 'App',
                    ],
                ]
            ],
            'seo_metadata' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Product' => 'Product',
                        'App\Models\ProductSet' => 'ProductSet',
                        'Domain\ProductSet\ProductSet' => 'ProductSet',
                        'App\Models\Discount' => 'Discount',
                        'App\Models\Page' => 'Page',
                        'Domain\Page\Page' => 'Page',
                    ],
                ]
            ],
            'prices' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Product' => 'Product',
                        'App\Models\Discount' => 'Discount',
                        'App\Models\Option' => 'Option',
                        'App\Models\Schema' => 'Schema',
                    ],
                ]
            ],
            'order_discounts' => [
                'model_type' => [
                    'id_column' => 'discount_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\Order' => 'Order',
                        'App\Models\OrderProduct' => 'OrderProduct',
                    ],
                ]
            ],
            'model_has_permissions' => [
                'model_type' => [
                    'id_column' => 'permission_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\App' => 'App',
                        'App\Models\User' => 'User',
                    ],
                ]
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
                ]
            ],
            'metadata_personals' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => 'User',
                    ],
                ]
            ],
            'metadata' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Order' => 'Order',
                        'App\Models\Discount' => 'Discount',
                        'App\Models\Banner' => 'Banner',
                        'Domain\Banner\Models\Banner' => 'Banner',
                        'App\Models\ProductSet' => 'ProductSet',
                        'Domain\ProductSet\ProductSet' => 'ProductSet',
                        'App\Models\AttributeOption' => 'AttributeOption',
                        'Domain\ProductAttribute\Models\AttributeOption' => 'AttributeOption',
                        'App\Models\Product' => 'Product',
                        'App\Models\Schema' => 'Schema',
                        'App\Models\Status' => 'Status',
                        'App\Models\Attribute' => 'Attribute',
                        'Domain\ProductAttribute\Models\Attribute' => 'Attribute',
                        'App\Models\Role' => 'Role',
                        'App\Models\Page' => 'Page',
                        'Domain\Page\Page' => 'Page',
                        'App\Models\Option' => 'Option',
                        'App\Models\Item' => 'Item',
                        'App\Models\App' => 'App',
                        'App\Models\User' => 'User',
                        'App\Models\Media' => 'Media',
                        'App\Models\ShippingMethod' => 'ShippingMethod',
                    ],
                ]
            ],
            'media_attachments' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Product' => 'Product',
                    ],
                ]
            ],
            'favourite_product_sets' => [
                'user_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => 'User',
                        'App\Models\App' => 'App',
                    ],
                ]
            ],
        ];
    }

    private function updateTypeValue(string $table, string $typeColumn, string $oldValue, string $newValue, string $idColumn, string $idSecondColumn = null): void
    {
        DB::table($table)
            ->orderBy($idColumn)
            ->where($typeColumn, '=', $oldValue)
            ->chunkById(100, function ($records) use ($table, $typeColumn, $newValue, $idColumn, $idSecondColumn) {
                foreach ($records as $record) {
                    $query = DB::table($table)
                        ->where($idColumn, '=', $record->$idColumn);
                    if ($idSecondColumn) {
                        $query->where($idSecondColumn, '=', $record->$idSecondColumn);
                    }
                    $query->update([
                        $typeColumn => $newValue,
                    ]);
                }
        }, $idColumn);
    }
};
