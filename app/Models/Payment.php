<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'method',
        'paid',
        'amount',
        'redirect_url',
        'continue_url',
        'created_at',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'amount' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
