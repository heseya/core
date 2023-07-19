<?php

namespace App\Http\Requests;

use App\Rules\ConsentsExists;
use App\Rules\RequiredConsents;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'birthday_date' => ['nullable', 'date', 'before_or_equal:now'],
            'phone' => ['nullable', 'phone:AUTO'],
            'consents' => ['nullable', 'array', new RequiredConsents()],
            'consents.*' => ['boolean', new ConsentsExists()],
            'preferences' => ['array'],
            'preferences.successful_login_attempt_alert' => ['boolean'],
            'preferences.failed_login_attempt_alert' => ['boolean'],
            'preferences.new_localization_login_alert' => ['boolean'],
            'preferences.recovery_code_changed_alert' => ['boolean'],
        ];
    }
}
