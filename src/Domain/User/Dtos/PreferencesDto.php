<?php

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Data;

class PreferencesDto extends Data
{
    public function __construct(
        #[BooleanType]
        public bool $successful_login_attempt_alert,
        #[BooleanType]
        public bool $failed_login_attempt_alert,
        #[BooleanType]
        public bool $new_localization_login_alert,
        #[BooleanType]
        public bool $recovery_code_changed_alert,
    ) {
    }
}
