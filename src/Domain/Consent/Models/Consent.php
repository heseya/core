<?php

namespace Domain\Consent\Models;

use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperConsent
 */
class Consent extends Model implements Translatable
{
    use HasFactory;
    use HasTranslations;

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
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
        'description_html',
    ];

    protected $casts = [
        'required' => 'boolean',
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
