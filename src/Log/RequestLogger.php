<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Cache\CachedResponse;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Response\AsyncContext;

/**
 * @internal
 *
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
            'request_body' => $this->getRequestBody($options),
            'response_status' => $status,
            'response_body' => $this->getResponseBody($responseOrContext, $options),
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

    /**
     * @param ApiClientOptions $options
     * @throws \JsonException
     */
    private function getRequestBody(array $options): ?string
    {
        if (!($options['user_data'][ApiClientInterface::LOG_REQUEST_BODY] ?? false)) {
            return null;
        }

        if (isset($options['json'])) {
            return json_encode($options['json'], JSON_THROW_ON_ERROR);
        }

        // TODO: handle other types of request body
        if (!isset($options['body']) || !is_string($options['body'])) {
            return null;
        }
        return $options['body'];
    }

    /**
     * @param ApiClientOptions $options
     * @throws \JsonException
     */
    private function getResponseBody(CachedResponse|AsyncContext $responseOrContext, array $options): ?string
    {
        $logResponse = $options['user_data'][ApiClientInterface::LOG_RESPONSE_BODY] ?? false;
        $logErrorResponse = $options['user_data'][ApiClientInterface::LOG_ERROR_RESPONSE_BODY] ?? false;
        if (!$logResponse && !($logErrorResponse && $responseOrContext->getStatusCode() >= 300)) {
            return null;
        }

        if ($responseOrContext instanceof AsyncContext) {
            if (null === $stream = $responseOrContext->getContent()) {
                return null;
            }
            return stream_get_contents($stream, offset: 0);
        }

        if ([] !== $jsonData = $responseOrContext->toArray(false)) {
            return json_encode($jsonData, JSON_THROW_ON_ERROR);
        }

        return $responseOrContext->getContent(false);
    }
}
