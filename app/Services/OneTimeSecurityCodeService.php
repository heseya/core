<?php

namespace App\Services;

use App\Models\OneTimeSecurityCode;
use App\Models\User;
use App\Notifications\TFARecoveryCodes;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OneTimeSecurityCodeService implements OneTimeSecurityCodeContract
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

    public function generateRecoveryCodes(int $codes = 3): array
    {
        Auth::user()->securityCodes()->delete();

        $recovery_codes = [];

        for ($i = 0; $i < $codes; $i++) {
            array_push($recovery_codes, $this->generateOneTimeSecurityCode(Auth::user()));
        }

        Auth::user()->notify(new TFARecoveryCodes());

        return $recovery_codes;
    }

    private function generateCode(array $modules, string $separator = ''): string
    {
        $code = '';

        foreach ($modules as $module) {
            $code .= Str::random($module) . $separator;
        }

        return Str::length($separator) === 0 ? $code : Str::replaceLast($separator, '', $code);
    }
}