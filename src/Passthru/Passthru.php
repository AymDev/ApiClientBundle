<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Passthru;

use AymDev\ApiClientBundle\Log\LogResponseProcessor;
use Symfony\Component\HttpClient\Response\AsyncContext as SymfonyAsyncContext;
use Symfony\Contracts\HttpClient\ChunkInterface;

class Passthru
{
    /** @var ResponseProcessorInterface[] */
    private array $responseProcessors = [];

    public function registerResponseProcessor(ResponseProcessorInterface $responseProcessor): void
    {
        $this->responseProcessors[] = $responseProcessor;
    }

    /**
     * Get a passthru callback
     * @return \Generator<ChunkInterface>
     */
    public function passthru(ChunkInterface $chunk, SymfonyAsyncContext $context): \Generator
    {
        // Response is fulfilled
        if ($chunk->isLast()) {
            $decoratedContext = new AsyncContext($context);
            foreach ($this->responseProcessors as $responseProcessor) {
                $responseProcessor->process($decoratedContext);
            }
        }

        yield $chunk;
    }

    public function setTracedRequestsGetterCallback(callable $callback): void
    {
        $this->findLogProcessor()?->setTracedRequestsGetterCallback($callback);
    }

    public function registerRequest(string $requestId): void
    {
        $this->findLogProcessor()?->registerRequest($requestId);
    }

    private function findLogProcessor(): ?LogResponseProcessor
    {
        foreach ($this->responseProcessors as $responseProcessor) {
            if ($responseProcessor instanceof LogResponseProcessor) {
                return $responseProcessor;
            }
        }
        return null;
    }
}
