<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Client;

use AymDev\ApiClientBundle\Cache\CachedResponse;
use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Client\ApiClient;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Log\Passthru;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ChunkInterface;

class ApiClientTest extends TestCase
{
    public function testRequestWithoutLog(): void
    {
        $passthru = self::createMock(Passthru::class);
        $passthru->expects(self::once())->method('setTracedRequestsGetterCallback');
        $passthru->expects(self::never())->method('registerRequest');
        $passthru->expects(self::never())->method('passthru');

        $client = new ApiClient(
            new OptionsParser(),
            $passthru,
            null,
            new MockHttpClient(new MockResponse()),
        );

        // No request ID
        $client->request('GET', 'https://example.com');
    }

    public function testRequestWithLog(): void
    {
        $requestId = 'requestId';

        $passthru = self::createMock(Passthru::class);
        $passthru->expects(self::once())->method('setTracedRequestsGetterCallback');
        $passthru->expects(self::once())->method('registerRequest')->with($requestId);
        $passthru->expects(self::atLeastOnce())
            ->method('passthru')
            ->willReturnCallback(function (ChunkInterface $chunk, AsyncContext $context): \Generator {
                yield $chunk;
            });

        $client = new ApiClient(
            new OptionsParser(),
            $passthru,
            null,
            new MockHttpClient()
        );

        $client->request('GET', 'https://example.com', [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $requestId,
            ],
        ]);
    }

    public function testRequestNotCached(): void
    {
        $cacheManager = self::createMock(CacheManager::class);
        $cacheManager->expects(self::once())->method('getFromCache')->willReturn(null);
        $cacheManager->expects(self::once())->method('getCacheableResponse')->willReturnArgument(0);

        $client = new ApiClient(
            new OptionsParser(),
            null,
            $cacheManager,
            new MockHttpClient(),
        );

        $response = $client->request('GET', 'https://example.com');
        self::assertInstanceOf(AsyncResponse::class, $response);
    }

    public function testCachedRequest(): void
    {
        $cachedResponse = self::createMock(CachedResponse::class);

        $cacheManager = self::createMock(CacheManager::class);
        $cacheManager->expects(self::once())->method('getFromCache')->willReturn($cachedResponse);
        $cacheManager->expects(self::never())->method('getCacheableResponse');

        $client = new ApiClient(
            new OptionsParser(),
            null,
            $cacheManager,
            new MockHttpClient(),
        );

        $response = $client->request('GET', 'https://example.com');
        self::assertSame($cachedResponse, $response);
    }

    public function testCacheableRequest(): void
    {
        $cacheableResponse = new MockResponse('cacheable');

        $cacheManager = self::createMock(CacheManager::class);
        $cacheManager->expects(self::once())->method('getFromCache')->willReturn(null);
        $cacheManager->expects(self::once())->method('getCacheableResponse')->willReturn($cacheableResponse);

        $client = new ApiClient(
            new OptionsParser(),
            null,
            $cacheManager,
            new MockHttpClient(),
        );

        $response = $client->request('GET', 'https://example.com');
        self::assertSame($cacheableResponse, $response);
    }
}
