<?php

namespace App\Tests\Mock;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class MockHttpResponse implements ResponseInterface
{
    private float $startTime;

    /**
     * @param array<mixed>|JsonSerializable|string $data
     */
    public function __construct(
        private array|JsonSerializable|string $data,
        #[ExpectedValues(valuesFromClass: Response::class)]
        private int $statusCode = Response::HTTP_OK,
    ) {
        $this->startTime = microtime(true);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(bool $throw = true): array
    {
        return [];
    }

    public function getContent(bool $throw = true): string
    {
        return is_string($this->data) ? $this->data : json_encode($this->data);
    }

    public function toArray(bool $throw = true): array
    {
        if (is_string($this->data)) {
            throw new JsonException('Invalid JSON');
        }

        return is_array($this->data) ? $this->data : $this->data->jsonSerialize();
    }

    public function cancel(): void
    {
    }

    public function getInfo(?string $type = null): mixed
    {
        $data = [
            'canceled' => false,
            'error' => null,
            'http_code' => $this->statusCode,
            'http_method' => 'GET',
            'redirect_count' => 0,
            'redirect_url' => null,
            'response_headers' => [],
            'start_time' => $this->startTime,
            'url' => '',
            'user_data' => null,
        ];

        return $type !== null ? $data[$type] ?? null : $data;
    }
}
