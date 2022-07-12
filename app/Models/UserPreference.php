<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperUserPreference
 */
class UserPreference extends Model
{
    protected $fillable = [
        'successful_login_attempt_alert',
        'failed_login_attempt_alert',
        'new_localization_login_alert',
        'recovery_code_changed_alert',
    ];

    protected $casts = [
        'successful_login_attempt_alert' => 'boolean',
        'failed_login_attempt_alert' => 'boolean',
        'new_localization_login_alert' => 'boolean',
        'recovery_code_changed_alert' => 'boolean',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'preferences_id');
    }
}
