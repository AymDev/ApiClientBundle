<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Client;

use AymDev\ApiClientBundle\Cache\CacheableResponse;
use AymDev\ApiClientBundle\Cache\CachedResponse;
use AymDev\ApiClientBundle\Cache\CachedResponseChunk;
use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Client\ApiClient;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Passthru\Passthru;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ChunkInterface;

class ApiClientTest extends TestCase
{
    private function getPassthruMock(): MockObject&Passthru
    {
        $passthru = self::createMock(Passthru::class);
        $passthru->expects(self::any())
            ->method('passthru')
            ->willReturnCallback(function (ChunkInterface $chunk, AsyncContext $context): \Generator {
                yield $chunk;
            });
        return $passthru;
    }

    public function testRequestWithoutId(): void
    {
        $passthru = $this->getPassthruMock();
        $passthru->expects(self::once())->method('setTracedRequestsGetterCallback');
        $passthru->expects(self::never())->method('registerRequest');

        $client = new ApiClient(
            new OptionsParser(),
            $passthru,
            null,
            new MockHttpClient(new MockResponse()),
        );

        // No request ID
        $client->request('GET', 'https://example.com');
    }

    public function testRequestWithId(): void
    {
        $requestId = 'requestId';

        $passthru = $this->getPassthruMock();
        $passthru->expects(self::once())->method('setTracedRequestsGetterCallback');
        $passthru->expects(self::once())->method('registerRequest')->with($requestId);

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
            $this->getPassthruMock(),
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
            $this->getPassthruMock(),
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
            $this->getPassthruMock(),
            $cacheManager,
            new MockHttpClient(),
        );

        $response = $client->request('GET', 'https://example.com');
        self::assertSame($cacheableResponse, $response);
    }

    public function testStreamWithCache(): void
    {
        $httpClient = new MockHttpClient();
        $firstRequestId = 'api-1';
        $secondRequestId = 'api-2';

        $client = new ApiClient(
            new OptionsParser(),
            $this->getPassthruMock(),
            new CacheManager(new OptionsParser(), new ArrayAdapter(), null),
            $httpClient,
        );

        $firstResponse = $client->request('GET', 'https://example.com', [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $firstRequestId,
                ApiClientInterface::CACHE_EXPIRATION => new \DateTime('tomorrow'),
            ]
        ]);
        $secondResponse = $client->request('GET', 'https://example.com', [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $secondRequestId,
                ApiClientInterface::CACHE_EXPIRATION => new \DateTime('tomorrow'),
            ]
        ]);

        foreach ($client->stream([$firstResponse, $secondResponse]) as $response => $chunk) {
            self::assertInstanceOf(CacheableResponse::class, $response);
            if ($chunk->isLast()) {
                // Trigger caching
                $response->getContent();
            }
        }

        $firstCachedResponse = $client->request('GET', 'https://example.com', [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $firstRequestId,
                ApiClientInterface::CACHE_EXPIRATION => new \DateTime('tomorrow'),
            ]
        ]);
        $secondCachedResponse = $client->request('GET', 'https://example.com', [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $secondRequestId,
                ApiClientInterface::CACHE_EXPIRATION => new \DateTime('tomorrow'),
            ]
        ]);

        foreach ($client->stream([$firstCachedResponse, $secondCachedResponse]) as $response => $chunk) {
            self::assertInstanceOf(CachedResponse::class, $response);
            self::assertInstanceOf(CachedResponseChunk::class, $chunk);
        }
        // Only 2 real requests should have been sent
        self::assertSame(2, $httpClient->getRequestsCount());
    }
}
