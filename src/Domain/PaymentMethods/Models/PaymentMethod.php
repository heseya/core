<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Models;

use App\Criteria\WhereHasOrderWithCode;
use App\Criteria\WhereHasShippingMethod;
use App\Criteria\WhereInIds;
use App\Models\App;
use App\Models\Model;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class PaymentMethod extends Model
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
        'type',
        'creates_default_payment',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'public' => 'boolean',
        'creates_default_payment' => 'boolean',
    ];
    /**
     * @var array|string[]
     */
    protected array $criteria = [
        'id',
        'public',
        'order_code' => WhereHasOrderWithCode::class,
        'shipping_method_id' => WhereHasShippingMethod::class,
        'alias',
        'ids' => WhereInIds::class,
    ];

    /**
     * @return BelongsToMany<ShippingMethod>
     */
    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'shipping_method_payment_method');
    }

    /**
     * @return BelongsTo<App, self>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * @return BelongsToMany<SalesChannel>
     */
    public function salesChannels(): BelongsToMany
    {
        return $this->belongsToMany(SalesChannel::class, 'sales_channel_payment_method');
    }
}
