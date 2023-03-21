<?php

namespace App\Models;

use Heseya\Searchable\Criteria\Equals;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperAppWidget
 */
class AppWidget extends Model
{
    use HasFactory;
    use HasCriteria;

    protected $fillable = [
        'app_id',
        'name',
        'url',
        'section',
    ];

    protected array $criteria = [
        'section' => Equals::class,
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
