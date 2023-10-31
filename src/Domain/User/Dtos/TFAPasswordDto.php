<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Models\User;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class TFAPasswordDto extends Data
{
    #[Computed]
    public User $user;

    public function __construct(
        #[Required, StringType, Max(255)]
        public string $password,
    ) {
        assert(request()->user() instanceof User);
        $this->user = request()->user();
    }
}
