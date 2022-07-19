<?php

namespace App\Jobs;

use App\Events\FailedLoginAttempt;
use App\Models\UserLoginAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ProcessFailedLoginAttempts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $userId,
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $now = Carbon::now();
        $attempts = UserLoginAttempt::where('user_id', $this->userId)
            ->where('created_at', '<=', $now->toDateTimeString())
            ->where('created_at', '>=', $now->subMinutes(5)->toDateTimeString())
            ->orderBy('created_at', 'desc')
            ->get();

        if ($attempts->doesntContain(['logged' => true])) {
            FailedLoginAttempt::dispatch($attempts->first());
        }
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->userId))->dontRelease()->expireAfter(300),
        ];
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}