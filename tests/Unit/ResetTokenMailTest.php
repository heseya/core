<?php

namespace Tests\Unit;

use App\Notifications\ResetPassword;
use Tests\TestCase;

class ResetTokenMailTest extends TestCase
{
    public function testHasCorrectUrl(): void
    {
        $notification = new ResetPassword('testtoken', 'https://example.com');
        $this->assertTrue(
            $notification->toMail($this->user)
                ->viewData['url'] === 'https://example.com?token=testtoken&email=' . urlencode($this->user->email),
        );
    }
}
