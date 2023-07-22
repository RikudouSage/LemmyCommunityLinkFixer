<?php

namespace App\Tests\Mock;

use BadMethodCallException;
use JsonSerializable;
use LogicException;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

final class MockHttpClient implements HttpClientInterface, ResetInterface
{
    use HttpClientTrait;

    /**
     * @param array<MockHttpResponse|array<mixed>|JsonSerializable|string|Throwable> $responses
     */
    public function __construct(
        public array $responses = [],
        public array $requests = [],
    ) {
    }

    public function request(string $method, string $url, array $options = []): MockHttpResponse
    {
        if (!count($this->responses)) {
            throw new LogicException('The mock response queue is empty');
        }

        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'options' => $options,
        ];

        $response = array_shift($this->responses);
        if ($response instanceof MockHttpResponse) {
            return $response;
        }
        if (is_string($response)) {
            $response = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        }

        if ($response instanceof Throwable) {
            throw $response;
        }

        return new MockHttpResponse($response);
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        throw new BadMethodCallException('Method not implemented');
    }

    public function reset(): void
    {
        $this->responses = [];
    }
}
