<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CacheableResponse extends AbstractCacheResponse
{
    private bool $savedInCache = false;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        public ResponseInterface $response,
        private readonly string $cacheKey,
        private readonly ?int $cacheTtl,
        private readonly ?\DateTimeInterface $cacheExpiry,
        private readonly ?int $cacheErrorTtl,
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->response->getHeaders($throw);
    }

    public function getContent(bool $throw = true): string
    {
        if (!$this->savedInCache) {
            // Error response caching
            if (null !== $this->cacheErrorTtl) {
                $content = $this->response->getContent(false);

                try {
                    $this->checkStatusCode();
                } catch (\Throwable $e) {
                    $this->saveInCache(content: $content, cacheTtl: $this->cacheErrorTtl);
                    if ($throw) {
                        throw $e;
                    } else {
                        return $content;
                    }
                }
            }

            if (!isset($content)) {
                $content = $this->response->getContent($throw);
            }

            $this->saveInCache(content: $content, cacheTtl: $this->cacheTtl, cacheExpiry: $this->cacheExpiry);
            return $content;
        }

        return $this->response->getContent($throw);
    }

    /**
     * @return mixed[]
     */
    public function toArray(bool $throw = true): array
    {
        if (!$this->savedInCache) {
            // Error response caching
            if (null !== $this->cacheErrorTtl) {
                $arrayContent = $this->response->toArray(false);

                try {
                    $this->checkStatusCode();
                } catch (\Throwable $e) {
                    $this->saveInCache(arrayContent: $arrayContent, cacheTtl: $this->cacheErrorTtl);
                    if ($throw) {
                        throw $e;
                    } else {
                        return $arrayContent;
                    }
                }
            }

            $arrayContent ??= $this->response->toArray($throw);
            $this->saveInCache(
                arrayContent: $arrayContent,
                cacheTtl: $this->cacheTtl,
                cacheExpiry: $this->cacheExpiry
            );
            return $arrayContent;
        }

        return $this->response->toArray($throw);
    }

    public function cancel(): void
    {
    }

    public function getInfo(?string $type = null): mixed
    {
        return $this->response->getInfo($type);
    }

    /**
     * @param mixed[] $arrayContent
     */
    private function saveInCache(
        string $content = '',
        array $arrayContent = [],
        ?int $cacheTtl = null,
        ?\DateTimeInterface $cacheExpiry = null
    ): void {
        $info = $this->response->getInfo();
        $info = is_array($info) ? $info : [$info];

        $cachedResponse = new CachedResponse(
            $this->response->getStatusCode(),
            $this->response->getHeaders(false),
            $content,
            $arrayContent,
            $this->filterClosuresRecursive($info),
        );

        $cacheItem = $this->cache->getItem($this->cacheKey);
        $cacheItem->set($cachedResponse);

        if (null !== $cacheExpiry) {
            $cacheItem->expiresAt($cacheExpiry);
        } elseif (null !== $cacheTtl) {
            $cacheItem->expiresAfter($cacheTtl);
        }

        $this->savedInCache = $this->cache->save($cacheItem);
    }

    /**
     * Delete closures than cannot be serialized in cache
     * @param mixed[] $data
     * @return mixed[]
     */
    private function filterClosuresRecursive(array $data): array
    {
        $filtered = array_filter($data, fn(mixed $element): bool => !($element instanceof \Closure));
        return array_map(
            fn (mixed $element): mixed => is_array($element) ? $this->filterClosuresRecursive($element) : $element,
            $filtered
        );
    }
}
