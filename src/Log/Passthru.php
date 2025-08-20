<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Contracts\HttpClient\ChunkInterface;

/**
 * @internal
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 *
 * @phpstan-type RequestOptions array{
 *       method: string,
 *       url: string,
 *       options: ApiClientOptions
 *   }
 *
 * This services manages async requests in order to log them
 */
class Passthru
{
    /** @var callable(): RequestOptions[] */
    private $getTracedRequests;

    /** @var array<string, float> */
    private array $loggableRequests = [];

    public function __construct(
        private readonly OptionsParser $optionsParser,
        private readonly RequestLogger $requestLogger,
    ) {
    }

    /**
     * Set the callback to get traced requests from the API client
     * TODO: find a more elegant way to get the requests
     */
    public function setTracedRequestsGetterCallback(callable $callback): void
    {
        $this->getTracedRequests = $callback;
    }

    /**
     * @return void
     */
    public function registerRequest(string $requestId): void
    {
        $this->loggableRequests[$requestId] = microtime(true);
    }

    /**
     * Get a passthru callback
     * @return \Generator<ChunkInterface>
     */
    public function passthru(ChunkInterface $chunk, AsyncContext $context): \Generator
    {
        $requestId = $this->optionsParser->getRequestId($context->getResponse());
        // No timer = request already logged
        $timerStart = $this->loggableRequests[$requestId] ?? null;

        // Response is fulfilled
        if ($chunk->isLast() && null !== $timerStart) {
            // Request duration
            $duration = microtime(true) - $timerStart;

            // Find request of current response in case of multiplexing
            $tracedRequests = array_filter(
                call_user_func($this->getTracedRequests),
                fn (array $r) => $requestId === $this->optionsParser->getRequestId($r['options'])
            );
            $request = array_values($tracedRequests)[0] ?? null;

            // Could not identify request
            if (null === $request) {
                return;
            }

            // Unregister request
            unset($this->loggableRequests[$requestId]);
            $this->requestLogger->logRequest($duration, $context, $request['options']);
        }

        yield $chunk;
    }
}
