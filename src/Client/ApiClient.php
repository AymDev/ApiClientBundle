<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Client;

use AymDev\ApiClientBundle\Log\Passthru;
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
        private readonly ?Passthru $passthru,
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
        // Log request when response is complete
        $passthru = null;
        $requestId = $this->optionsParser->getRequestId(options: $options);

        if (null !== $this->passthru && null !== $requestId) {
            $this->passthru->registerRequest($requestId);
            $passthru = $this->passthru->passthru(...);
        }

        $options['buffer'] = true;
        return new AsyncResponse($this->httpClient, $method, $url, $options, $passthru);
    }
}
