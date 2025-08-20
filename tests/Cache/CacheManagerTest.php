<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Cache;

use AymDev\ApiClientBundle\Cache\CacheableResponse;
use AymDev\ApiClientBundle\Cache\CachedResponse;
use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Log\RequestLogger;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpClient\Response\MockResponse;

class CacheManagerTest extends TestCase
{
    public function testGetFromCacheWithoutOptions(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('hasCacheOptions')->willReturn(false);

        $cacheManager = new CacheManager(
            $optionsParser,
            self::createMock(CacheItemPoolInterface::class),
            null,
        );

        self::assertNull($cacheManager->getFromCache([]));
    }

    public function testGetFromCacheNotFound(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('hasCacheOptions')->willReturn(true);
        $optionsParser->expects(self::once())->method('getRequestId')->willReturn('request-id');

        $item = self::createMock(CacheItemInterface::class);
        $item->expects(self::once())->method('isHit')->willReturn(false);

        $cache = self::createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturn($item);

        $cacheManager = new CacheManager(
            $optionsParser,
            $cache,
            null,
        );

        self::assertNull($cacheManager->getFromCache([]));
    }

    public function testGetFromCacheWithoutLog(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('hasCacheOptions')->willReturn(true);
        $optionsParser->expects(self::once())->method('getRequestId')->willReturn('request-id');

        $response = new CachedResponse(200, [], '', [], []);

        $item = self::createMock(CacheItemInterface::class);
        $item->expects(self::once())->method('isHit')->willReturn(true);
        $item->expects(self::once())->method('get')->willReturn($response);

        $cache = self::createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturn($item);

        $cacheManager = new CacheManager(
            $optionsParser,
            $cache,
            null,
        );

        self::assertSame($response, $cacheManager->getFromCache([]));
    }

    public function testGetFromCacheWithLog(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('hasCacheOptions')->willReturn(true);
        $optionsParser->expects(self::once())->method('getRequestId')->willReturn('request-id');

        $response = new CachedResponse(200, [], '', [], []);

        $item = self::createMock(CacheItemInterface::class);
        $item->expects(self::once())->method('isHit')->willReturn(true);
        $item->expects(self::once())->method('get')->willReturn($response);

        $cache = self::createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturn($item);

        $requestLogger = self::createMock(RequestLogger::class);
        $requestLogger->expects(self::once())->method('logRequest');

        $cacheManager = new CacheManager(
            $optionsParser,
            $cache,
            $requestLogger,
        );

        self::assertSame($response, $cacheManager->getFromCache([]));
    }

    public function testGetCacheableResponseWithoutOptions(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('hasCacheOptions')->willReturn(false);

        $cacheManager = new CacheManager(
            $optionsParser,
            self::createMock(CacheItemPoolInterface::class),
            null,
        );

        $response = new MockResponse();
        self::assertSame($response, $cacheManager->getCacheableResponse($response));
    }

    public function testGetCacheableResponse(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('hasCacheOptions')->willReturn(true);
        $optionsParser->expects(self::once())->method('getRequestId')->willReturn('request-id');

        $cacheManager = new CacheManager(
            $optionsParser,
            self::createMock(CacheItemPoolInterface::class),
            null,
        );

        self::assertInstanceOf(
            CacheableResponse::class,
            $cacheManager->getCacheableResponse(new CachedResponse(200, [], '', [], []))
        );
    }
}
