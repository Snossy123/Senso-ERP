<?php

namespace App\Services\Shipping;

use Illuminate\Http\Client\Response;
use RuntimeException;

class QpExpressException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly mixed $body = null,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response, string $fallback = 'QP Express request failed'): self
    {
        $body = $response->json() ?? $response->body();
        $message = $response->json('message')
            ?? $response->json('detail')
            ?? $fallback;

        return new self((string) $message, $response->status(), $body);
    }
}
