<?php

declare(strict_types=1);

namespace Domain\Email\Services;

use App\Mail\RawEmail;
use Domain\Email\Dtos\EmailDto;
use Illuminate\Support\Facades\Mail;

final class EmailService
{
    public function sendEmail(EmailDto $dto): void
    {
        Mail::to($dto->receiver)
            ->send(new RawEmail($dto->body, $dto->title));
    }
}
