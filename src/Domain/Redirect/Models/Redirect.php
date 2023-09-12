<?php

namespace Domain\Redirect\Models;

use App\Models\IdeHelperRedirect;
use App\Traits\HasUuid;
use Domain\Redirect\Enums\RedirectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperRedirect
 */
class Redirect extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'url',
        'type',
    ];
    protected $casts = [
        'type' => RedirectType::class,
    ];
}
