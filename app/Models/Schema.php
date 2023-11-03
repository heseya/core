<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\SchemaSearch;
use App\Criteria\TranslatedLike;
use App\Criteria\WhereInIds;
use App\Models\Contracts\SortableContract;
use App\Models\Interfaces\Translatable;
use App\SortColumnTypes\TranslatedColumn;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use App\Traits\Sortable;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property string $name
 * @property string $description
 *
 * @mixin IdeHelperSchema
 */
class Schema extends Model implements SortableContract, Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use Sortable;

    public const HIDDEN_PERMISSION = 'schemas.show_hidden';

    protected $fillable = [
        'name',
        'description',
        'hidden',
        'required',
        'max',
        'min',
        'step',
        'default',
        'available',
        'shipping_time',
        'shipping_date',
        'published',
        'product_id',
    ];

    protected array $translatable = [
        'name',
        'description',
    ];

    protected $casts = [
        'hidden' => 'bool',
        'required' => 'bool',
        'available' => 'bool',
        'published' => 'array',
    ];

    protected array $criteria = [
        'search' => SchemaSearch::class,
        'name' => TranslatedLike::class,
        'hidden',
        'required',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'schemas.published' => Like::class,
    ];

    protected array $sortable = [
        'name' => TranslatedColumn::class,
        'sku',
        'created_at',
        'updated_at',
    ];

    /**
     * Check if user input is valid.
     *
     * @throws ValidationException
     */
    public function validate(mixed $value): void
    {
        $validation = new Collection();

        if ($this->required) {
            $validation->push('required');
        } elseif ($value === null) {
            return;
        }

        if ($this->max) {
            $validation->push('max:' . $this->max);
        }

        if ($this->min) {
            $validation->push('min:' . $this->min);
        }

        $validationStrings = [
            'attribute' => $this->name,
            'min' => $this->min,
            'max' => $this->max,
        ];

        $validator = Validator::make(
            [$this->getKey() => $value],
            [$this->getKey() => $validation],
            [
                'required' => Lang::get('validation.schema.required', $validationStrings),
                'numeric' => Lang::get('validation.schema.numeric', $validationStrings),
                'uuid' => Lang::get('validation.schema.uuid', $validationStrings),
                'min' => Lang::get('validation.schema.min', $validationStrings),
                'max' => Lang::get('validation.schema.max', $validationStrings),
            ],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function getItems(int|string|null $value, float $quantity = 0): array
    {
        $items = [];

        if ($value === null) {
            return $items;
        }

        $option = $this->options()->find($value);

        if ($option?->items) {
            foreach ($option->items as $item) {
                $items[$item->getKey()] = $quantity;
            }
        }

        return $items;
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)
            ->with(['items'])
            ->orderBy('order')
            ->orderBy('created_at')
            ->orderBy('name', 'DESC');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_schemas');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function getPrice(mixed $value, array $schemas, Currency $currency): Money
    {
        $schemaKeys = Collection::make($schemas)->keys();

        if ($this->usedBySchemas()->whereIn($this->getKeyName(), $schemaKeys)->exists()) {
            return Money::zero($currency->value);
        }

        return $this->getUsedPrice($value, $schemas, $currency);
    }

    public function usedBySchemas(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'schema_used_schemas',
            'used_schema_id',
            'schema_id',
        );
    }

    public function usedSchemas(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'schema_used_schemas',
            'schema_id',
            'used_schema_id',
        );
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    private function getUsedPrice(mixed $value, array $schemas, Currency $currency): Money
    {
        if (!$this->required && $value === null) {
            return Money::zero($currency->value);
        }

        $usedSchema = $this->usedSchemas()->first();
        if (!empty($usedSchema)) {
            return $usedSchema->getUsedPrice(
                $schemas[$usedSchema->getKey()],
                $schemas,
                $currency,
            )->multipliedBy($value);
        }

        $price = Money::zero($currency->value);

        $option = $this->options()->find($value);
        if ($option?->count() > 0) {
            /** @var Option $option */
            $price = $price->plus($option->getPriceForCurrency($currency));
        } elseif (!Str::isUuid($value) && is_numeric($value)) {
            $price = $price->multipliedBy($value);
        }

        return $price;
    }
}
