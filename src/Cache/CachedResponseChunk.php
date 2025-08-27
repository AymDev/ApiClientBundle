<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Cache;

use Symfony\Contracts\HttpClient\ChunkInterface;

class CachedResponseChunk implements ChunkInterface
{
    public function __construct(
        private readonly CachedResponse $response,
    ) {
    }

    public function isTimeout(): bool
    {
        return false;
    }

    public function isFirst(): bool
    {
        return false;
    }

    public function isLast(): bool
    {
        return true;
    }

    /**
     * @return null|mixed[]
     */
    public function getInformationalStatus(): ?array
    {
        return null;
    }

    public function getContent(): string
    {
        return $this->response->getContent(false);
    }

    public function getOffset(): int
    {
        return strlen($this->response->getContent(false));
    }

    public function getError(): ?string
    {
        return null;
    }
}
