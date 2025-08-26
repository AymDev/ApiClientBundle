<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Passthru\ContextInterface;
use AymDev\ApiClientBundle\Passthru\ResponseProcessorInterface;

/**
 * @internal
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 *
 * @phpstan-type RequestOptions array{
 *       method: string,
 *       url: string,
 *       options: ApiClientOptions
 *   }
 */
class LogResponseProcessor implements ResponseProcessorInterface
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

    public function process(ContextInterface $context): void
    {
        $requestId = $this->optionsParser->getRequestId($context->getResponse());
        if (null === $this->requestLogger || null === $requestId) {
            return;
        }

        // No timer = request already logged
        $timerStart = $this->loggableRequests[$requestId] ?? null;
        if (null === $timerStart) {
            return;
        }

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

    /**
     * Set the callback to get traced requests from the API client
     * TODO: find a more elegant way to get the requests
     */
    public function setTracedRequestsGetterCallback(callable $callback): void
    {
        $this->getTracedRequests = $callback;
    }

    public function registerRequest(string $requestId): void
    {
        $this->loggableRequests[$requestId] = microtime(true);
    }
}
