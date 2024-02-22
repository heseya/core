<?php

declare(strict_types=1);

namespace Domain\Auth\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\TokenType;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\Token;
use App\Models\User;
use Exception;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser;
use PHPOpenSourceSaver\JWTAuth\Manager;
use PHPOpenSourceSaver\JWTAuth\Payload;

final class TokenService
{
    private JWTCustom $jwt;
    private EloquentUserProvider $userProvider;
    private EloquentUserProvider $appProvider;

    public function __construct(Manager $manager)
    {
        $this->jwt = new JWTCustom($manager, new Parser(new Request()));
        $this->userProvider = new EloquentUserProvider(app(Hasher::class), User::class);
        $this->appProvider = new EloquentUserProvider(app(Hasher::class), App::class);
    }

    public function validate(string $token): bool
    {
        $this->jwt->setToken($token);

        return (bool) $this->jwt->check();
    }

    public function getUser(string $token): ?Authenticatable
    {
        if (!$this->validate($token)) {
            return null;
        }

        $this->jwt->setToken($token);

        if ($this->jwt->checkSubjectModel($this->userProvider->getModel())) {
            return $this->userProvider->retrieveById($this->jwt->getClaim('sub'));
        }

        if ($this->jwt->checkSubjectModel($this->appProvider->getModel())) {
            return $this->appProvider->retrieveById($this->jwt->getClaim('sub'));
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function payloadNoValidate(string $token): array
    {
        $this->jwt->setToken($token);

        return $this->jwt->getPayloadNoValidation();
    }

    public function payload(string $token): ?Payload
    {
        $this->jwt->setToken($token);

        return $this->validate($token) ? $this->jwt->payload() : null;
    }

    public function createToken(JWTSubject $user, TokenType $type, ?string $uuid = null): string
    {
        $exp = match ($type) {
            TokenType::ACCESS => Config::get('jwt.ttl', 5),
            TokenType::IDENTITY => Config::get('jwt.ttl', 5),
            TokenType::REFRESH => 60 * 24 * 90,
        };

        $this->jwt->factory()->setTTL($exp);

        $claims = [
            'iss' => Config::get('app.url'),
            'typ' => $type->value,
        ];

        if ($uuid !== null) {
            $claims['jti'] = $uuid;
        }

        if ($user instanceof App && $type->is(TokenType::REFRESH)) {
            $claims['url'] = $user->url;
            $claims['key'] = Str::random();
            $user->update([
                'refresh_token_key' => $claims['key'],
            ]);
        }

        return $this->jwt->claims($claims)->fromUser($user);
    }

    public function invalidateToken(string $token): void
    {
        $payload = $this->payload($token);

        if ($payload !== null) {
            try {
                Token::updateOrCreate([
                    'id' => $payload->get('jti'),
                    'invalidated' => true,
                    'expires_at' => $payload->get('exp'),
                ]);
            } catch (Exception $error) {
                throw new ClientException(Exceptions::CLIENT_INVALID_TOKEN);
            }
        }
    }

    public function isTokenType(string $token, TokenType $type): bool
    {
        $payload = $this->payload($token);

        if ($payload === null) {
            return false;
        }

        return $type->is($payload->get('typ'));
    }
}
