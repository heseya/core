<?php

namespace App\Services;

use App\Events\NewLocalizationLoginAttempt;
use App\Events\SuccessfulLoginAttempt;
use App\Jobs\ProcessFailedLoginAttempts;
use App\Models\User;
use App\Models\UserLoginAttempt;
use App\Services\Contracts\UserLoginAttemptServiceContract;
use Illuminate\Support\Facades\Request;

class UserLoginAttemptService implements UserLoginAttemptServiceContract
{
    public function store(bool $logged = false): void
    {
        $user = User::where('email', Request::input('email'))->first();

        if ($user) {
            $ip = Request::ip();
            $userAgent = Request::userAgent();
            $fingerprint = sha1("${ip} ${userAgent}");

            $attempt = $user->loginAttempts()->create([
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'fingerprint' => $fingerprint,
                'logged' => $logged,
            ]);

            if ($logged) {
                $this->sendSuccessfulLoginAttemptAlert($attempt);
            } else {
                $this->sendFailedLoginAttemptAlert($attempt);
            }
            $this->sendNewLocalizationLoginAlert($attempt);
        }
    }

    private function sendFailedLoginAttemptAlert(UserLoginAttempt $attempt): void
    {
        $preferences = $attempt->user?->preferences;

        if ($preferences !== null && $preferences->failed_login_attempt_alert) {
            ProcessFailedLoginAttempts::dispatch($attempt->user_id)->delay(now()->addMinutes(5));
        }
    }

    private function sendSuccessfulLoginAttemptAlert(UserLoginAttempt $attempt): void
    {
        $preferences = $attempt->user?->preferences;

        if ($preferences !== null && $preferences->successful_login_attempt_alert) {
            SuccessfulLoginAttempt::dispatch($attempt);
        }
    }

    private function sendNewLocalizationLoginAlert(UserLoginAttempt $attempt): void
    {
        $preferences = $attempt->user?->preferences;

        if ($preferences !== null && $preferences->new_localization_login_alert) {
            $attempts = UserLoginAttempt::where('user_id', $attempt->user_id)
                ->where('fingerprint', $attempt->fingerprint)
                ->where('created_at', '<', $attempt->created_at)
                ->count();

            if ($attempts === 0) {
                NewLocalizationLoginAttempt::dispatch($attempt);
            }
        }
    }
}
