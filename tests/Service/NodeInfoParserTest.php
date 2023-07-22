<?php

namespace App\Tests\Service;

use App\Service\NodeInfoParser;
use App\Tests\Mock\MockHttpClient;
use App\Tests\Mock\MockHttpResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpFoundation\Response;

class NodeInfoParserTest extends TestCase
{
    private NodeInfoParser $instance;

    private array $responses;

    protected function setUp(): void
    {
        $httpClient = new MockHttpClient();
        $this->responses = &$httpClient->responses;
        $this->instance = new NodeInfoParser($httpClient);
    }

    public function testGetSoftware(): void
    {
        // invalid url
        $this->assertNull($this->instance->getSoftware('/test'));

        // client exception (like 404)
        $this->responses[] = new ClientException(new MockHttpResponse([], Response::HTTP_NOT_FOUND));
        $this->assertNull($this->instance->getSoftware('lemmy://test@example.com'));

        // server exception
        $this->responses[] = new ServerException(new MockHttpResponse([], Response::HTTP_INTERNAL_SERVER_ERROR));
        $this->assertNull($this->instance->getSoftware('https://example.com'));

        // invalid json
        $this->responses[] = new MockHttpResponse('test');
        $this->assertNull($this->instance->getSoftware('https://example.com'));

        // transport exception
        $this->responses[] = new TimeoutException();
        $this->assertNull($this->instance->getSoftware('https://example.com'));

        // not found link
        $this->createLinkResponse();
        $this->responses[] = new ClientException(new MockHttpResponse([], Response::HTTP_NOT_FOUND));
        $this->assertNull($this->instance->getSoftware('https://example.com'));

        $this->responses[] = [
            'links' => [
                ['rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0'],
            ],
        ];
        $this->assertNull($this->instance->getSoftware('https://example.com'));

        $this->createLinkResponse();
        $this->responses[] = [
            'software' => [],
        ];
        $this->assertNull($this->instance->getSoftware('https://example.com'));

        $this->createLinkResponse();
        $this->createSoftwareResponse('lemmy');

        $this->assertSame('lemmy', $this->instance->getSoftware('https://example.com'));
    }

    private function createLinkResponse(): void
    {
        $this->responses[] = [
            'links' => [
                [
                    'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                    'href' => 'https://example.com/nodeinfo/2.0.json',
                ],
            ],
        ];
    }

    private function createSoftwareResponse(string $software): void
    {
        $this->responses[] = [
            'software' => [
                'name' => $software,
            ],
        ];
    }
}
