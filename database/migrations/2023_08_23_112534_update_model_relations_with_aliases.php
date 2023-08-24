<?php

use App\Enums\RelationAlias;
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
                        'App\Models\User' => RelationAlias::USER->value,
                        'App\Models\App' => RelationAlias::APP->value,
                    ],
                ],
            ],
            'model_has_discounts' => [
                'model_type' => [
                    'id_column' => 'discount_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\Product' => RelationAlias::PRODUCT->value,
                        'App\Models\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'Domain\ProductSet\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'App\Models\ShippingMethod' => RelationAlias::SHIPPING_METHOD->value,
                    ],
                ]
            ],
            'wishlist_products' => [
                'user_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => RelationAlias::USER->value,
                        'App\Models\App' => RelationAlias::APP->value,
                    ],
                ]
            ],
            'web_hooks' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => RelationAlias::USER->value,
                        'App\Models\App' => RelationAlias::APP->value,
                    ],
                ]
            ],
            'seo_metadata' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Product' => RelationAlias::PRODUCT->value,
                        'App\Models\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'Domain\ProductSet\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'App\Models\Discount' => RelationAlias::DISCOUNT->value,
                        'App\Models\Page' => RelationAlias::PAGE->value,
                        'Domain\Page\Page' => RelationAlias::PAGE->value,
                    ],
                ]
            ],
            'prices' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Product' => RelationAlias::PRODUCT->value,
                        'App\Models\Discount' => RelationAlias::DISCOUNT->value,
                        'App\Models\Option' => RelationAlias::OPTION->value,
                        'App\Models\Schema' => RelationAlias::SCHEMA->value,
                    ],
                ]
            ],
            'order_discounts' => [
                'model_type' => [
                    'id_column' => 'discount_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\Order' => RelationAlias::ORDER->value,
                        'App\Models\OrderProduct' => RelationAlias::ORDER_PRODUCT->value,
                    ],
                ]
            ],
            'model_has_permissions' => [
                'model_type' => [
                    'id_column' => 'permission_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\App' => RelationAlias::APP->value,
                        'App\Models\User' => RelationAlias::USER->value,
                    ],
                ]
            ],
            'model_has_discount_conditions' => [
                'model_type' => [
                    'id_column' => 'discount_condition_id',
                    'id_second_column' => 'model_id',
                    'relations' => [
                        'App\Models\Product' => RelationAlias::PRODUCT->value,
                        'App\Models\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'Domain\ProductSet\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'App\Models\User' => RelationAlias::USER->value,
                        'App\Models\Role' => RelationAlias::ROLE->value,
                    ],
                ]
            ],
            'metadata_personals' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => RelationAlias::USER->value,
                    ],
                ]
            ],
            'metadata' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Order' => RelationAlias::ORDER->value,
                        'App\Models\Discount' => RelationAlias::DISCOUNT->value,
                        'App\Models\Banner' => RelationAlias::BANNER->value,
                        'Domain\Banner\Models\Banner' => RelationAlias::BANNER->value,
                        'App\Models\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'Domain\ProductSet\ProductSet' => RelationAlias::PRODUCT_SET->value,
                        'App\Models\AttributeOption' => RelationAlias::ATTRIBUTE_OPTION->value,
                        'Domain\ProductAttribute\Models\AttributeOption' => RelationAlias::ATTRIBUTE_OPTION->value,
                        'App\Models\Product' => RelationAlias::PRODUCT->value,
                        'App\Models\Schema' => RelationAlias::SCHEMA->value,
                        'App\Models\Status' => RelationAlias::STATUS->value,
                        'App\Models\Attribute' => RelationAlias::ATTRIBUTE->value,
                        'Domain\ProductAttribute\Models\Attribute' => RelationAlias::ATTRIBUTE->value,
                        'App\Models\Role' => RelationAlias::ROLE->value,
                        'App\Models\Page' => RelationAlias::PAGE->value,
                        'Domain\Page\Page' => RelationAlias::PAGE->value,
                        'App\Models\Option' => RelationAlias::OPTION->value,
                        'App\Models\Item' => RelationAlias::ITEM->value,
                        'App\Models\App' => RelationAlias::APP->value,
                        'App\Models\User' => RelationAlias::USER->value,
                        'App\Models\Media' => RelationAlias::MEDIA->value,
                        'App\Models\ShippingMethod' => RelationAlias::SHIPPING_METHOD->value,
                    ],
                ]
            ],
            'media_attachments' => [
                'model_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\Product' => RelationAlias::PRODUCT->value,
                    ],
                ]
            ],
            'favourite_product_sets' => [
                'user_type' => [
                    'id_column' => 'id',
                    'id_second_column' => null,
                    'relations' => [
                        'App\Models\User' => RelationAlias::USER->value,
                        'App\Models\App' => RelationAlias::APP->value,
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
        });
    }
};
