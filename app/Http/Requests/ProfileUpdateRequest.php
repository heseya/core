<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\ConsentExists;
use App\Rules\RequiredConsentsUpdate;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'consents.*',
        'preferences.successful_login_attempt_alert',
        'preferences.failed_login_attempt_alert',
        'preferences.new_localization_login_alert',
        'preferences.recovery_code_changed_alert',
    ];

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'consents.*' => [new ConsentExists(), new Boolean()],
            'consents' => ['nullable', 'array', new RequiredConsentsUpdate()],
            'preferences' => ['array'],
            'preferences.successful_login_attempt_alert' => [new Boolean()],
            'preferences.failed_login_attempt_alert' => [new Boolean()],
            'preferences.new_localization_login_alert' => [new Boolean()],
            'preferences.recovery_code_changed_alert' => [new Boolean()],
        ];
    }
}
