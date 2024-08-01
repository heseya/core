<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\SchemaSearch;
use App\Criteria\TranslatedLike;
use App\Criteria\WhereInIds;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SchemaType;
use App\Exceptions\ClientException;
use App\Models\Contracts\SortableContract;
use App\Models\Interfaces\Translatable;
use App\Rules\OptionAvailable;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @deprecated
 *
 * @property string $name
 * @property string $description
 * @property SchemaType $type
 * @property Collection<int, Price> $prices
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
        'type',
        'name',
        'description',
        'hidden',
        'required',
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
        'type' => SchemaType::class,
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
     * @throws ClientException
     */
    public function validate(mixed $value): void
    {
        $validation = new Collection();

        if ($this->required) {
            $validation->push('required');
        } elseif ($value === null) {
            return;
        }

        if ($this->type->is(SchemaType::SELECT)) {
            $validation->push('uuid');
            $validation->push(new OptionAvailable($this));
        }

        if (
            in_array($this->type, [
                SchemaType::NUMERIC,
                SchemaType::MULTIPLY,
                SchemaType::MULTIPLY_SCHEMA,
            ])
        ) {
            $validation->push('numeric');
        }

        $validationStrings = [
            'attribute' => $this->name,
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
            throw new ClientException(Exceptions::CLIENT_PRODUCT_OPTION, errorArray: $validator->messages()->messages());
        }
    }

    public function getItems(int|string|null $value, float $quantity = 0): array
    {
        $items = [];

        if ($value === null || $this->type !== SchemaType::SELECT) {
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

    public function setTypeAttribute(mixed $value): void
    {
        if (is_string($value)) {
            $value = SchemaType::fromName($value);
        }

        $this->setEnumCastableAttribute('type', $value);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_schemas');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }

    public function getPriceForCurrency(Currency $currency): Money
    {
        return $this->prices->where('currency', $currency->value)->firstOrFail()->value;
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

        if (
            ($this->type->is(SchemaType::STRING) || $this->type->is(SchemaType::NUMERIC))
            && Str::length(trim($value)) === 0
        ) {
            return Money::zero($currency->value);
        }

        if ($this->type->is(SchemaType::BOOLEAN) && ((bool) $value) === false) {
            return Money::zero($currency->value);
        }

        if ($this->type->is(SchemaType::MULTIPLY_SCHEMA)) {
            /** @var Schema $usedSchema */
            $usedSchema = $this->usedSchemas()->firstOrFail();

            return $usedSchema->getUsedPrice(
                $schemas[$usedSchema->getKey()],
                $schemas,
                $currency,
            )->multipliedBy($value);
        }

        $price = $this->getPriceForCurrency($currency);

        if ($this->type->is(SchemaType::SELECT)) {
            /** @var Option $option */
            $option = $this->options()->findOrFail($value);
            $price = $price->plus($option->getPriceForCurrency($currency));
        }

        if ($this->type->is(SchemaType::MULTIPLY)) {
            if ($value === null) {
                return Money::zero($currency->value);
            }
            $price = $price->multipliedBy($value);
        }

        return $price;
    }

    public function getMorphClass()
    {
        return 'Schema';
    }
}
