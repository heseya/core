<?php

declare(strict_types=1);

namespace Domain\App\Models;

use App\Models\App;
use App\Models\Model;
use Domain\Auth\Criteria\UserHasCorrectPermissions;
use Heseya\Searchable\Criteria\Equals;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasPermissions;

/**
 * @mixin IdeHelperAppWidget
 */
final class AppWidget extends Model
{
    use HasCriteria;
    use HasFactory;
    use HasPermissions;

    protected $table = 'app_widgets';

    protected string $guard_name = 'api';

    protected $fillable = [
        'app_id',
        'name',
        'url',
        'section',
    ];

    /** @var array<string, class-string> */
    protected array $criteria = [
        'section' => Equals::class,
        'app_id' => Equals::class,
        'permissions' => UserHasCorrectPermissions::class,
    ];

    /**
     * @return BelongsTo<App,self>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
