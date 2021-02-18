<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema()
 */
class Schema extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="type",
     *   type="string",
     *   enum={"string", "numeric", "boolean", "date", "select", "file"},
     * )
     */
    public const TYPES = [
        0 => 'string',
        1 => 'numeric',
        2 => 'boolean',
        3 => 'date',
        4 => 'select',
        5 => 'file',
    ];

    /**
     * @OA\Property(
     *   property="name",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="description",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="price",
     *   type="float",
     * ),
     * @OA\Property(
     *   property="hidden",
     *   type="boolean",
     * ),
     * @OA\Property(
     *   property="required",
     *   type="boolean",
     * ),
     * @OA\Property(
     *   property="min",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="max",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="default",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="pattern",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="validation",
     *   type="string",
     * ),
     */
    protected $fillable = [
        'type',
        'name',
        'description',
        'price',
        'hidden',
        'required',
        'max',
        'min',
        'default',
        'pattern',
        'validation',
    ];

    /**
     * Check if user input is valid
     *
     * @param mixed $input
     *
     * @return bool
     */
    public function validate($input): bool
    {
        $validator = Validator::make($input, $this->validation);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_schemas');
    }

    /**
     * @OA\Property(
     *   property="options",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Option"),
     * )
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }
}
