<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Passthru;

use Symfony\Component\HttpClient\Response\AsyncContext as SymfonyAsyncContext;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
class AsyncContext implements ContextInterface
{
    public function __construct(
        private readonly SymfonyAsyncContext $asyncContext,
    ) {
    }

    public function getResponse(): ResponseInterface
    {
        return $this->asyncContext->getResponse();
    }

    public function getStatusCode(): int
    {
        return $this->asyncContext->getStatusCode();
    }

    public function getResponseBody(): ?string
    {
        $stream = $this->asyncContext->getContent();
        if (null === $stream) {
            return null;
        }

        $content = stream_get_contents($stream, offset: 0);
        if (false === $content) {
            return null;
        }
        return $content;
    }

    public function getInfo(?string $key = null): mixed
    {
        return $this->asyncContext->getInfo($key);
    }
}
