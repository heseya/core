<?php

namespace App\Services\Contracts;

use App\Enums\TokenType;
use Illuminate\Contracts\Auth\Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Payload;

interface TokenServiceContract
{
    /**
     * Returns whether token is valid or not
     *
     * @param string $token
     *
     * @return Payload|null
     */
    public function validate(string $token): bool;

    /**
     * Returns user identified by the token or null if the token is invalid
     *
     * @param string $token
     *
     * @return Payload|null
     */
    public function getUser(string $token): ?Authenticatable;

    /**
     * Returns token payload or null if the token is invalid
     *
     * @param string $token
     *
     * @return Payload|null
     */
    public function payload(string $token): ?Payload;

    /**
     * Returns new token for a given user
     *
     * @param JWTSubject $user
     * @param TokenType $type
     *
     * @return string
     */
    public function createToken(JWTSubject $user, TokenType $type, ?string $uuid): string;

    /**
     * Invalidates the given token
     *
     * @param string $token
     */
    public function invalidateToken(string $token): void;
}
