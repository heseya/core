<?php

namespace App\Channels;

use Illuminate\Support\Facades\Config;
use Spatie\WebhookServer\Signer\Signer;

class WebHookSigner implements Signer
{
    public function signatureHeaderName(): string
    {
        return Config::get('webhook-server.signature_header_name');
    }

    public function calculateSignature(string $webhookUrl, array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $payloadJson, $secret);
    }
}
