<?php

declare(strict_types=1);

namespace Domain\User\Services;

use App\Events\TfaRecoveryCodesChanged;
use App\Mail\TFARecoveryCodes;
use App\Models\OneTimeSecurityCode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class OneTimeSecurityCodeService
{
    public function generateOneTimeSecurityCode(User $user, int $expires_time = 0): string
    {
        $code = $this->generateCode([5, 5], '-');

        OneTimeSecurityCode::create([
            'code' => Hash::make($code),
            'expires_at' => $expires_time > 0 ? Carbon::now()->addMilliseconds($expires_time) : null,
            'user_id' => $user->getKey(),
        ]);

        return $code;
    }

    /**
     * @return array<string>
     */
    public function generateRecoveryCodes(string $locale, int $codes = 3): array
    {
        /** @var User $user */
        $user = Auth::user();
        $user->securityCodes()->delete();
        $recovery_codes = [];

        for ($i = 0; $i < $codes; ++$i) {
            $recovery_codes[] = $this->generateOneTimeSecurityCode($user);
        }

        if ($user->preferences !== null && $user->preferences->recovery_code_changed_alert) {
            TfaRecoveryCodesChanged::dispatch($user);
            Mail::to($user->email)
                ->locale($locale)
                ->send(new TFARecoveryCodes());
        }

        return $recovery_codes;
    }

    /**
     * @param array<int> $modules
     */
    private function generateCode(array $modules, string $separator = ''): string
    {
        $code = '';

        foreach ($modules as $module) {
            $code .= Str::random($module) . $separator;
        }

        return Str::length($separator) === 0 ? $code : Str::replaceLast($separator, '', $code);
    }
}
