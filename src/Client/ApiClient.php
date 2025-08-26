<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Client;

use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Passthru\Passthru;
use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 */
class ApiClient implements ApiClientInterface
{
    use AsyncDecoratorTrait;

    private TraceableHttpClient $httpClient;

    public function __construct(
        private readonly OptionsParser $optionsParser,
        private readonly Passthru $passthru,
        private readonly ?CacheManager $cacheManager,
        HttpClientInterface $httpClient,
    ) {
        $this->httpClient = new TraceableHttpClient($httpClient);
        $this->passthru?->setTracedRequestsGetterCallback($this->httpClient->getTracedRequests(...));
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
}
