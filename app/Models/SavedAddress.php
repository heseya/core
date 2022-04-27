<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSavedAddress
 */
class SavedAddress extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'default',
        'name',
        'address_id',
        'user_id',
        'type',
    ];
    protected $casts = [
        'default' => 'bool',
        'name' => 'string',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
