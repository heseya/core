<?php

declare(strict_types=1);

namespace Domain\Email\Controllers;

use App\Http\Controllers\Controller;
use Domain\Email\Dtos\EmailDto;
use Domain\Email\Services\EmailService;
use Illuminate\Support\Facades\Response;

final class EmailController extends Controller
{
    public function __construct(
        private readonly EmailService $emailService,
    ) {}

    public function sendEmail(EmailDto $dto): \Illuminate\Http\Response
    {
        $this->emailService->sendEmail($dto);

        return Response::noContent();
    }
}
