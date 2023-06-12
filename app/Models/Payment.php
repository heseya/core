<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PaymentStatus $status
 *
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'method',
        'redirect_url',
        'continue_url',
        'created_at',
        'status',
        'order_id',
        'method_id',
        // ///
        'amount',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        // ///
        'amount' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }
}
