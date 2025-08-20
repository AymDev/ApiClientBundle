<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Cache\CachedResponse;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Response\AsyncContext;

/**
 * @internal
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 */
class RequestLogger
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param ApiClientOptions $options
     */
    public function logRequest(
        float $duration,
        CachedResponse|AsyncContext $responseOrContext,
        array $options,
    ): void {
        $message = sprintf(
            '%sAPI call {response_status} {method} {url}',
            $responseOrContext instanceof CachedResponse ? 'Cached ' : '',
        );

        $status = $responseOrContext->getInfo('http_code');
        $responseOrContext = [
            'method' => $responseOrContext->getInfo('http_method'),
            'url' => $responseOrContext->getInfo('url'),
            'response_status' => $status,
            'time' => $duration,
            'cache' => $responseOrContext instanceof CachedResponse,
            'error' => $responseOrContext->getInfo('error'),
        ];

        if (is_scalar($status) && intval($status) >= 400) {
            $this->logger->error($message, $responseOrContext);
        } else {
            $this->logger->info($message, $responseOrContext);
        }
    }
}
