<?php

namespace Tests\Unit;

use App\Mail\ResetPassword;
use Tests\TestCase;

class ResetTokenMailTest extends TestCase
{
    public function testHasCorrectUrl(): void
    {
        $mail = new ResetPassword('https://example.com?token=testtoken&email=' . urlencode($this->user->email), $this->user->name);
        $mail->assertSeeInHtml('https://example.com?token=testtoken&email=' . urlencode($this->user->email));
    }
}
