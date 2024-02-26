<?php

namespace App\Models;

use App\Criteria\WhereHasOrderWithCode;
use App\Criteria\WhereHasShippingMethod;
use App\Criteria\WhereInIds;
use Domain\App\Models\App;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperPaymentMethod
 */
class PaymentMethod extends Model
{
    use HasCriteria;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'public',
        'icon',
        'url',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'public' => 'boolean',
    ];
    protected array $criteria = [
        'id',
        'public',
        'order_code' => WhereHasOrderWithCode::class,
        'shipping_method_id' => WhereHasShippingMethod::class,
        'alias',
        'ids' => WhereInIds::class,
    ];

    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'shipping_method_payment_method');
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
