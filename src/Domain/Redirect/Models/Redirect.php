<?php

namespace Domain\Redirect\Models;

use App\Models\IdeHelperRedirect;
use App\Traits\HasUuid;
use Domain\Redirect\Enums\RedirectType;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperRedirect
 */
class Redirect extends Model
{
    use HasCriteria;
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'name',
        'source_url',
        'target_url',
        'type',
        'enabled',
    ];
    protected $casts = [
        'type' => RedirectType::class,
        'enabled' => 'boolean',
    ];
    protected array $criteria = [
        'enabled',
    ];
}
