<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserPreferencesResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'successful_login_attempt_alert' => $this->resource->successful_login_attempt_alert,
            'failed_login_attempt_alert' => $this->resource->failed_login_attempt_alert,
            'new_localization_login_alert' => $this->resource->new_localization_login_alert,
            'recovery_code_changed_alert' => $this->resource->recovery_code_changed_alert,
        ];
    }
}
