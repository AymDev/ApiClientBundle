<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Cache;

class CachedResponse extends AbstractCacheResponse
{
    /**
     * @param array<string, list<string>> $headers
     * @param mixed[] $arrayContent
     * @param mixed[] $info
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $content,
        private readonly array $arrayContent,
        private readonly array $info,
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->headers;
    }

    public function getContent(bool $throw = true): string
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->content;
    }

    /**
     * @return mixed[]
     */
    public function toArray(bool $throw = true): array
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->arrayContent;
    }

    public function cancel(): void
    {
    }

    public function getInfo(?string $type = null): mixed
    {
        return null !== $type
            ? $this->info[$type] ?? null
            : $this->info;
    }
}
