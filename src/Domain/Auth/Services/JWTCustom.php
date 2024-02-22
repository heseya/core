<?php

declare(strict_types=1);

namespace Domain\Auth\Services;

use PHPOpenSourceSaver\JWTAuth\JWT;

final class JWTCustom extends JWT
{
    /**
     * @return array<string, string>
     */
    public function getPayloadNoValidation(): array
    {
        // @phpstan-ignore-next-line
        return $this->manager->getJWTProvider()->decode($this->token);
    }
}
