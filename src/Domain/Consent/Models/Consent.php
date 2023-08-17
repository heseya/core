<?php

declare(strict_types=1);

namespace Domain\Consent\Models;

use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\User;
use App\Traits\CustomHasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperConsent
 */
final class Consent extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasFactory;

    protected const HIDDEN_PERMISSION = 'consents.show_hidden';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'consents';

    protected $fillable = [
        'name',
        'description_html',
        'required',
        'published',
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
        'description_html',
    ];

    protected $casts = [
        'required' => 'boolean',
        'published' => 'array',
    ];

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('value')
            ->using(ConsentUser::class);
    }
}
