<?php

declare(strict_types=1);

namespace Domain\User\Traits;

use App\Notifications\VerifyEmail;
use Illuminate\Auth\MustVerifyEmail as IlluminateMustVerifyEmail;

trait MustVerifyEmail
{
    use IlluminateMustVerifyEmail {
        markEmailAsVerified as private parentMarkEmailAsVerified;
    }

    public function markEmailAsUnverified()
    {
        // @var Model&MustVerifyEmail $this
        return $this->forceFill([
            'email_verified_at' => null,
            'email_verify_token' => sha1($this->getKey() . '|' . $this->getEmailForVerification()),
        ])->save();
    }

    public function markEmailAsVerified()
    {
        // @var Model&MustVerifyEmail $this
        $this->parentMarkEmailAsVerified();

        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'email_verify_token' => null,
            'email_verify_url' => null,
        ])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }
}
