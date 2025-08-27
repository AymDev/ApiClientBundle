<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Cache;

use AymDev\ApiClientBundle\Cache\CacheableResponse;
use AymDev\ApiClientBundle\Cache\CachedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CacheableResponseTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $statusCode = 200;

        $internalResponse = self::createMock(ResponseInterface::class);
        $internalResponse->expects(self::once())->method('getStatusCode')->willReturn($statusCode);

        $response = new CacheableResponse(
            new NullAdapter(),
            $internalResponse,
            'requestId',
            'cache_key',
            null,
            null,
            null,
        );
        self::assertSame($statusCode, $response->getStatusCode());
    }

    public function testGetHeaders(): void
    {
        $headers = ['Header' => ['value']];

        $internalResponse = self::createMock(ResponseInterface::class);
        $internalResponse->expects(self::once())->method('getHeaders')->willReturn($headers);

        $response = new CacheableResponse(
            new NullAdapter(),
            $internalResponse,
            'requestId',
            'cache_key',
            null,
            null,
            null,
        );
        self::assertSame($headers, $response->getHeaders());
    }

    public function testGetContentWithCacheTtl(): void
    {
        $content = 'Hello World';
        $cacheTtl = 3600;

        // Response
        $internalResponse = self::createMock(ResponseInterface::class);
        $internalResponse->expects(self::exactly(2))->method('getContent')->willReturn($content);
        $internalResponse->method('getInfo')->willReturn([]);

        // Cache
        $cacheItem = self::createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())->method('set')->with(self::isInstanceOf(CachedResponse::class));
        $cacheItem->expects(self::once())->method('expiresAfter')->with(self::callback(fn ($v) => $v === $cacheTtl));

        $cache = self::createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturn($cacheItem);
        $cache->expects(self::once())->method('save')->willReturn(true);

        $response = new CacheableResponse(
            $cache,
            $internalResponse,
            'requestId',
            'cache_key',
            $cacheTtl,
            null,
            null,
        );

        // Double call: caching only performed once
        self::assertSame($content, $response->getContent());
        self::assertSame($content, $response->getContent());
    }

    public function testGetArrayContentWithCacheExpiry(): void
    {
        $arrayContent = ['key' => 'value'];
        $expiry = new \DateTime('tomorrow');

        // Response
        $internalResponse = self::createMock(ResponseInterface::class);
        $internalResponse->expects(self::exactly(2))->method('toArray')->willReturn($arrayContent);
        $internalResponse->method('getInfo')->willReturn([]);

        // Cache
        $cacheItem = self::createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())->method('set')->with(self::isInstanceOf(CachedResponse::class));
        $cacheItem->expects(self::once())->method('expiresAt')->with(self::callback(fn ($v) => $v === $expiry));

        $cache = self::createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturn($cacheItem);
        $cache->expects(self::once())->method('save')->willReturn(true);

        $response = new CacheableResponse(
            $cache,
            $internalResponse,
            'requestId',
            'cache_key',
            null,
            $expiry,
            null,
        );

        // Double call: caching only performed once
        self::assertSame($arrayContent, $response->toArray());
        self::assertSame($arrayContent, $response->toArray());
    }

    public function testGetContentWithCacheErrorTtl(): void
    {
        $content = 'Hello World';
        $cacheErrorTtl = 60;

        // Response
        $internalResponse = new CachedResponse(500, [], $content, [], []);

        // Cache
        $cacheItem = self::createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())->method('set')->with(self::isInstanceOf(CachedResponse::class));
        $cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with(self::callback(fn ($v) => $v === $cacheErrorTtl));

        $cache = self::createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturn($cacheItem);
        $cache->expects(self::once())->method('save')->willReturn(true);

        $response = new CacheableResponse(
            $cache,
            $internalResponse,
            'requestId',
            'cache_key',
            $cacheErrorTtl * 2,
            null,
            $cacheErrorTtl,
        );

        self::assertSame($content, $response->getContent(false));
    }

    public function testGetInfo(): void
    {
        $info = 'info';

        $internalResponse = self::createMock(ResponseInterface::class);
        $internalResponse->expects(self::once())->method('getInfo')->willReturn($info);

        $response = new CacheableResponse(
            new NullAdapter(),
            $internalResponse,
            'requestId',
            'cache_key',
            null,
            null,
            null,
        );
        self::assertSame($info, $response->getInfo());
    }
}
