<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Client;

use AymDev\ApiClientBundle\Cache\CacheableResponse;
use AymDev\ApiClientBundle\Cache\CachedResponse;
use AymDev\ApiClientBundle\Cache\CachedResponseChunk;
use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Passthru\Passthru;
use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 */
class ApiClient implements ApiClientInterface
{
    use AsyncDecoratorTrait {
        stream as internalStream;
    }

    private TraceableHttpClient $httpClient;

    public function __construct(
        private readonly OptionsParser $optionsParser,
        private readonly Passthru $passthru,
        private readonly ?CacheManager $cacheManager,
        HttpClientInterface $httpClient,
    ) {
        $this->httpClient = new TraceableHttpClient($httpClient);
        $this->passthru->setTracedRequestsGetterCallback($this->httpClient->getTracedRequests(...));
    }

    /**
     * @param ApiClientOptions $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // Response already in cache
        $cachedResponse = $this->cacheManager?->getFromCache($options);
        if (null !== $cachedResponse) {
            return $cachedResponse;
        }

        // Log request when response is complete
        if (null !== $requestId = $this->optionsParser->getRequestId($options)) {
            $this->passthru->registerRequest($requestId);
        }

        $options['buffer'] = true;
        $response = new AsyncResponse($this->httpClient, $method, $url, $options, $this->passthru->passthru(...));
        return $this->cacheManager?->getCacheableResponse($response) ?? $response;
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        if ($responses instanceof ResponseInterface) {
            $responses = [$responses];
        }

        return new ResponseStream($this->doStream($responses, $timeout));
    }

    /**
     * @param iterable<ResponseInterface> $responses
     * @return \Generator<ResponseInterface, ChunkInterface>
     */
    public function doStream(iterable $responses, ?float $timeout = null): \Generator
    {
        $cacheableResponses = [];
        $asyncResponses = [];

        // Send cached responses and group others by type
        foreach ($responses as $response) {
            if ($response instanceof CachedResponse) {
                yield $response => new CachedResponseChunk($response);
            } elseif ($response instanceof CacheableResponse) {
                $cacheableResponses[$response->requestId] = $response;
                $asyncResponses[] = $response->response;
            } else {
                $asyncResponses[] = $response;
            }
        }

        foreach ($this->internalStream($asyncResponses) as $response => $chunk) {
            // Detect cacheable response
            $requestId = $this->optionsParser->getRequestId($response);
            if (null !== $requestId) {
                $cacheableResponse = $cacheableResponses[$requestId] ?? null;
                if (null !== $cacheableResponse) {
                    // Replace with decorator response
                    $response = $cacheableResponse;
                }
            }

            yield $response => $chunk;
        }
    }
}
