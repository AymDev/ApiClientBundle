<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Response\AsyncContext;

/**
 * @internal
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 *
 * @phpstan-type RequestOptions array{
 *      method: string,
 *      url: string,
 *      options: ApiClientOptions
 *  }
 */
class RequestLogger
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param RequestOptions $request
     */
    public function logRequest(
        float $duration,
        AsyncContext $context,
        array $request,
    ): void {
        $message = 'API call {response_status} {method} {url}';

        $status = $context->getInfo('http_code');
        $context = [
            'method' => $context->getInfo('http_method'),
            'url' => $context->getInfo('url'),
            'response_status' => $status,
            'time' => $duration,
            'error' => $context->getInfo('error'),
        ];

        if (is_scalar($status) && intval($status) >= 400) {
            $this->logger->error($message, $context);
        } else {
            $this->logger->info($message, $context);
        }
    }
}
