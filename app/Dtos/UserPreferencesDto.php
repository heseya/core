<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;

class UserPreferencesDto extends Dto implements InstantiateFromRequest
{
    private bool|Missing $successful_login_attempt_alert;
    private bool|Missing $failed_login_attempt_alert;
    private bool|Missing $new_localization_login_alert;
    private bool|Missing $recovery_code_changed_alert;

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            successful_login_attempt_alert: $request
                ->input('preferences.successful_login_attempt_alert', new Missing()),
            failed_login_attempt_alert: $request
                ->input('preferences.failed_login_attempt_alert', new Missing()),
            new_localization_login_alert: $request
                ->input('preferences.new_localization_login_alert', new Missing()),
            recovery_code_changed_alert: $request
                ->input('preferences.recovery_code_changed_alert', new Missing()),
        );
    }

    public function getSuccessfulLoginAttemptAlert(): bool|Missing
    {
        return $this->successful_login_attempt_alert;
    }

    public function getFailedLoginAttemptAlert(): bool|Missing
    {
        return $this->failed_login_attempt_alert;
    }

    public function getNewLocalizationLoginAlert(): bool|Missing
    {
        return $this->new_localization_login_alert;
    }

    public function getRecoveryCodeChangedAlert(): bool|Missing
    {
        return $this->recovery_code_changed_alert;
    }
}
