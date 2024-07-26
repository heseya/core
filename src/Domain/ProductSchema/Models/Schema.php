<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\SchemaHasProduct;
use App\Criteria\SchemaSearch;
use App\Criteria\TranslatedLike;
use App\Criteria\WhereInIds;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SchemaType;
use App\Exceptions\ClientException;
use App\Models\Contracts\SortableContract;
use App\Models\Interfaces\Translatable;
use App\Models\Item;
use App\Models\Model;
use App\Models\Option;
use App\Models\Product;
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * @property string $name
 * @property string $description
 * @property int|SchemaType $type
 * @property bool $required
 * @property Collection<int, Option> $options
 *
 * @mixin IdeHelperSchema
 */
final class Schema extends Model implements SortableContract, Translatable
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

    /** @var string[] */
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

    /** @var string[] */
    protected array $criteria = [
        'search' => SchemaSearch::class,
        'name' => TranslatedLike::class,
        'hidden',
        'required',
        'has_product' => SchemaHasProduct::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
        'published' => Like::class,
        'schemas.published' => Like::class,
    ];

    /** @var string[] */
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

        $validation->push('uuid');
        $validation->push(new OptionAvailable($this));

        $validationStrings = [
            'attribute' => $this->name,
        ];

        $validator = Validator::make(
            [$this->getKey() => $value],
            [$this->getKey() => $validation],
            [
                'required' => Lang::get('validation.schema.required', $validationStrings),
                'uuid' => Lang::get('validation.schema.uuid', $validationStrings),
            ],
        );

        if ($validator->fails()) {
            throw new ClientException(Exceptions::CLIENT_PRODUCT_OPTION, errorArray: $validator->messages()->messages());
        }
    }

    /**
     * @return array<int|string,float>
     */
    public function getItems(int|string|null $value, float $quantity = 0): array
    {
        if ($value === null) {
            return [];
        }

        /** @var Option|null $option */
        $option = $this->options()->find($value);

        $items = [];
        if ($option?->items) {
            /** @var Item $item */
            foreach ($option->items as $item) {
                $items[$item->getKey()] = $quantity;
            }
        }

        return $items;
    }

    /**
     * @return HasMany<Option>
     */
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
        $this->setEnumCastableAttribute('type', SchemaType::SELECT);
    }

    /**
     * @deprecated
     *
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_schemas');
    }

    /**
     * @return BelongsTo<Product,self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @param array<string,Schema> $schemas
     *
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

    /**
     * @return BelongsToMany<Schema>
     */
    public function usedBySchemas(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'schema_used_schemas',
            'used_schema_id',
            'schema_id',
        );
    }

    /**
     * @return BelongsToMany<Schema>
     */
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
     * @param array<string,Schema> $schemas
     *
     * @throws MathException
     * @throws MoneyMismatchException
     */
    private function getUsedPrice(mixed $value, array $schemas, Currency $currency): Money
    {
        if (!$this->required && $value === null) {
            return Money::zero($currency->value);
        }

        /** @var Schema|null $usedSchema */
        $usedSchema = $this->usedSchemas()->first();
        if ($usedSchema !== null) {
            return $usedSchema->getUsedPrice(
                $schemas[$usedSchema->getKey()],
                $schemas,
                $currency,
            )->multipliedBy($value);
        }

        try {
            /** @var Option $option */
            $option = $this->options()->findOrFail($value);
        } catch (Throwable $th) {
            return Money::zero($currency->value);
        }

        return $option->getPriceForCurrency($currency);
    }
}
