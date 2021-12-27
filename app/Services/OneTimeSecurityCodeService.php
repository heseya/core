<?php

namespace App\Services;

use App\Models\OneTimeSecurityCode;
use App\Models\User;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class OneTimeSecurityCodeService implements OneTimeSecurityCodeContract
{
    public function generateOneTimeSecurityCode(User $user, int $expires_time = 0): string
    {
        $code = $this->generateCode([5, 5], '-');

        OneTimeSecurityCode::create([
            'code' => Crypt::encryptString($code),
            'expires_at' => $expires_time > 0 ? Carbon::now()->addMilliseconds($expires_time) : null,
            'user_id' => $user->getKey(),
        ]);

        return $code;
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
