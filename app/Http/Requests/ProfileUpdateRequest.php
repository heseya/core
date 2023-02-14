<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\ConsentExists;
use App\Rules\RequiredConsentsUpdate;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'birthday_date' => ['nullable', 'date', 'before_or_equal:now'],
            'phone' => ['nullable', 'phone:AUTO'],
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
