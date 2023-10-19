<?php

declare(strict_types=1);

namespace Domain\Organization\Models;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizationToken extends Model
{
    protected $fillable = [
        'id',
        'organization_id',
        'email',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    /**
     * @return BelongsTo<Organization, self>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
