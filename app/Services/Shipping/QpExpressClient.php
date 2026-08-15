<?php

namespace App\Services\Shipping;

use App\Models\ShippingIntegration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class QpExpressClient
{
    public function __construct(private readonly ShippingIntegration $integration) {}

    public function authenticate(bool $force = false): string
    {
        $cacheKey = $this->tokenCacheKey();

        if (! $force) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $response = Http::timeout((int) config('shipping.timeout', 20))
            ->acceptJson()
            ->asJson()
            ->post($this->url('integration/token'), [
                'username' => $this->integration->username,
                'password' => $this->integration->password,
            ]);

        if ($response->failed()) {
            throw QpExpressException::fromResponse($response, 'QP Express authentication failed');
        }

        $token = (string) $response->json('token');
        if ($token === '') {
            throw new QpExpressException('QP Express token was empty.', $response->status(), $response->json());
        }

        Cache::put($cacheKey, $token, now()->addMinutes((int) config('shipping.token_ttl_minutes', 50)));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload): array
    {
        return $this->json('POST', 'integration/order', ['json' => $payload]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string|int $serial): array
    {
        return $this->json('GET', 'integration/order/'.$serial);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listOrders(array $filters = []): array
    {
        return $this->json('GET', 'integration/order', ['query' => $filters]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateOrder(string|int $serial, array $payload): array
    {
        return $this->json('PATCH', 'integration/order/'.$serial, ['json' => $payload]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getUpdateHistory(array $filters = []): array
    {
        $data = $this->json('GET', 'integration/get_order_update_history', ['query' => $filters]);

        if (array_is_list($data)) {
            return $data;
        }

        return $data['results'] ?? $data['data'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function json(string $method, string $path, array $options = [], bool $retried = false): array
    {
        $response = $this->send($method, $path, $options);

        if ($response->status() === 401 && ! $retried) {
            $this->authenticate(true);

            return $this->json($method, $path, $options, true);
        }

        if ($response->failed()) {
            throw QpExpressException::fromResponse($response);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function send(string $method, string $path, array $options = []): Response
    {
        return Http::timeout((int) config('shipping.timeout', 20))
            ->acceptJson()
            ->asJson()
            ->withToken($this->authenticate())
            ->send($method, $this->url($path), $options);
    }

    private function url(string $path): string
    {
        return $this->integration->resolvedBaseUrl().ltrim($path, '/');
    }

    private function tokenCacheKey(): string
    {
        return 'qp_express_token_'.$this->integration->id;
    }
}
