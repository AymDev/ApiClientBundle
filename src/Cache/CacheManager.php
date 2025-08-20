<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Cache;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Log\RequestLogger;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 *
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 * @phpstan-import-type UserDataOptions from ApiClientInterface
 */
class CacheManager
{
    private const string CACHE_PREFIX = 'aymdev_api_client.response';

    public function __construct(
        private readonly OptionsParser $optionsParser,
        private readonly CacheItemPoolInterface $cache,
        private readonly ?RequestLogger $requestLogger,
    ) {
    }

    /**
     * @param ApiClientOptions $options
     */
    public function getFromCache(array $options): ?CachedResponse
    {
        if (!$this->optionsParser->hasCacheOptions($options)) {
            return null;
        }

        $cacheItem = $this->cache->getItem($this->createCacheKey($this->optionsParser->getRequestId($options)));
        if (!$cacheItem->isHit()) {
            return null;
        }

        $timerStart = microtime(true);

        /** @var CachedResponse $response */
        $response = $cacheItem->get();

        $duration = microtime(true) - $timerStart;

        // Log cached call
        $this->requestLogger?->logRequest($duration, $response, $options);
        return $response;
    }

    public function getCacheableResponse(ResponseInterface $response): ResponseInterface
    {
        if (!$this->optionsParser->hasCacheOptions($response)) {
            return $response;
        }

        /** @var mixed[]&UserDataOptions $userData */
        $userData = $response->getInfo('user_data');
        return new CacheableResponse(
            $this->cache,
            $response,
            $this->createCacheKey($this->optionsParser->getRequestId($response)),
            $userData[ApiClientInterface::CACHE_DURATION] ?? null,
            $userData[ApiClientInterface::CACHE_EXPIRATION] ?? null,
            $userData[ApiClientInterface::CACHE_ERROR_DURATION] ?? null,
        );
    }

    private function createCacheKey(?string $requestId): string
    {
        if (null === $requestId) {
            throw new \LogicException('The request ID must be set to cache the response.');
        }

        return sprintf('%s.%s', self::CACHE_PREFIX, $requestId);
    }
}
