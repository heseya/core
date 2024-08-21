<?php

namespace Tests\Feature\Emails;

use App\Mail\RawEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTest extends TestCase
{
    public function testSendEmailUnauthorized(): void
    {
        Mail::fake();

        $this->json('POST', 'email', [
            'title' => 'Test',
            'receiver' => 'example@example.com',
            'body' => '<div>Hello there</div>'
        ])->assertForbidden();

        Mail::assertNothingSent();
    }

    /**
     * @dataProvider authProvider
     */
    public function testSendEmail(string $user): void
    {
        $this->{$user}->givePermissionTo('email.send');

        Mail::fake();

        $this->actingAs($this->{$user})->json('POST', 'email', [
            'title' => 'Test',
            'receiver' => 'example@example.com',
            'body' => '<div>Hello there</div>'
        ])
            ->assertNoContent();

        Mail::assertSent(RawEmail::class, function (RawEmail $mail): bool {
            $mail->assertTo('example@example.com');
            $mail->assertHasSubject('Test');
            $mail->assertSeeInHtml('Hello there');

            return true;
        });

//        Mail::shouldReceive('html')->with('<div>Hello there</div>')->once();
//        Mail::shouldReceive('html')->with('<div>Hello there</div>', \Mockery::type('callable'))->once();
    }
}
