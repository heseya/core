<?php

namespace App\Channels;

use Illuminate\Support\Facades\Config;
use JsonException;
use Spatie\WebhookServer\Signer\Signer;

class WebHookSigner implements Signer
{
    public function signatureHeaderName(): string
    {
        return Config::get('webhook-server.signature_header_name');
    }

    /**
     * @throws JsonException
     */
    public function calculateSignature(string $webhookUrl, array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $payloadJson, $secret);
    }
}
