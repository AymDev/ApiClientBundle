<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Cache;

use AymDev\ApiClientBundle\Cache\CachedResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class CachedResponseTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $response = new CachedResponse(200, [], '', [], []);
        self::assertSame(200, $response->getStatusCode());
    }

    public function testGetHeaders(): void
    {
        $headers = ['Header' => ['value']];

        // 2xx response
        $response = new CachedResponse(200, $headers, '', [], []);
        self::assertSame($headers, $response->getHeaders());

        // 5xx response with no exception
        $response = new CachedResponse(500, $headers, '', [], []);
        self::assertSame($headers, $response->getHeaders(false));
    }

    /**
     * Read headers from a 3xx/4xx/5xx response
     * @param class-string<HttpExceptionInterface> $exception
     */
    #[DataProvider('provideUnsuccessfulResponses')]
    public function testGetHeadersOnUnsuccessfulResponse(CachedResponse $response, string $exception): void
    {
        self::expectException($exception);
        $response->getHeaders();
    }

    public function testGetContent(): void
    {
        $content = 'Hello World';

        // 2xx response
        $response = new CachedResponse(200, [], $content, [], []);
        self::assertSame($content, $response->getContent());

        // 5xx response with no exception
        $response = new CachedResponse(500, [], $content, [], []);
        self::assertSame($content, $response->getContent(false));
    }

    /**
     * Read content from a 3xx/4xx/5xx response
     * @param class-string<HttpExceptionInterface> $exception
     */
    #[DataProvider('provideUnsuccessfulResponses')]
    public function testGetContentOnUnsuccessfulResponse(CachedResponse $response, string $exception): void
    {
        self::expectException($exception);
        $response->getContent();
    }

    public function testGetArrayContent(): void
    {
        $arrayContent = ['key' => 'value'];

        // 2xx response
        $response = new CachedResponse(200, [], '', $arrayContent, []);
        self::assertSame($arrayContent, $response->toArray());

        // 5xx response with no exception
        $response = new CachedResponse(500, [], '', $arrayContent, []);
        self::assertSame($arrayContent, $response->toArray(false));
    }

    /**
     * Read array content from a 3xx/4xx/5xx response
     * @param class-string<HttpExceptionInterface> $exception
     */
    #[DataProvider('provideUnsuccessfulResponses')]
    public function testGetArrayContentOnUnsuccessfulResponse(CachedResponse $response, string $exception): void
    {
        self::expectException($exception);
        $response->toArray();
    }

    /**
     * Provide responses with 3xx/4xx/5xx statuses
     * @return array{CachedResponse, class-string<HttpExceptionInterface>}[]
     */
    public static function provideUnsuccessfulResponses(): array
    {
        $info = [
            'response_headers' => [],
            'http_code' => 0,
        ];

        return [
            [
                new CachedResponse(300, [], '', [], $info),
                RedirectionException::class,
            ],
            [
                new CachedResponse(400, [], '', [], $info),
                ClientException::class,
            ],
            [
                new CachedResponse(500, [], '', [], $info),
                ServerException::class,
            ],
        ];
    }

    public function testGetInfo(): void
    {
        $info = [
            'key' => 'value',
        ];

        $response = new CachedResponse(200, [], '', [], $info);

        self::assertSame($info, $response->getInfo());
        self::assertSame('value', $response->getInfo('key'));
        self::assertNull($response->getInfo('undefined_key'));
    }
}
