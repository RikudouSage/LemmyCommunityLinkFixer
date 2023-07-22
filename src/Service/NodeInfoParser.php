<?php

namespace App\Service;

use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class NodeInfoParser
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function getSoftware(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            // @codeCoverageIgnoreStart
            // I honestly have no idea how to make parse_url return false
            return null;
            // @codeCoverageIgnoreEnd
        }
        if (!isset($parts['scheme'])) {
            $parts['scheme'] = 'https';
        }
        if ($parts['scheme'] === 'lemmy') {
            $parts['scheme'] = 'https';
        }
        if (!isset($parts['host'])) {
            return null;
        }
        $wellKnownUrl = $parts['scheme'] . '://' . $parts['host'] . '/.well-known/nodeinfo';

        try {
            $response = json_decode($this->httpClient->request(
                Request::METHOD_GET,
                $wellKnownUrl,
            )->getContent(), true, flags: JSON_THROW_ON_ERROR);
            assert(is_array($response));
        } catch (ClientExceptionInterface|JsonException|ServerExceptionInterface|TransportExceptionInterface) {
            return null;
        }

        foreach (($response['links'] ?? []) as $link) {
            $checkUrl = $link['href'] ?? null;
            if ($checkUrl === null) {
                continue;
            }

            try {
                $checkResponse = json_decode($this->httpClient->request(
                    Request::METHOD_GET,
                    $checkUrl,
                )->getContent(), true, flags: JSON_THROW_ON_ERROR);
                assert(is_array($checkResponse));
            } catch (ClientExceptionInterface|JsonException|ServerExceptionInterface|TransportExceptionInterface) {
                continue;
            }

            $software = $checkResponse['software']['name'] ?? null;
            if ($software === null) {
                continue;
            }

            return strtolower($software);
        }

        return null;
    }
}
