<?php

declare(strict_types=1);

namespace Tests\AymDevApiClientBundle\Client;

use AymDev\ApiClientBundle\Client\ApiClient;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Log\Passthru;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ChunkInterface;

class ApiClientTest extends TestCase
{
    public function testRequestWithoutLog(): void
    {
        $passthru = self::createMock(Passthru::class);
        $passthru->expects(self::once())->method('setTracedRequestsGetterCallback');
        $passthru->expects(self::never())->method('registerRequest');
        $passthru->expects(self::never())->method('passthru');

        $client = new ApiClient(
            new OptionsParser(),
            $passthru,
            new MockHttpClient(new MockResponse()),
        );

        // No request ID
        $client->request('GET', 'https://example.com');
    }

    public function testRequestWithLog(): void
    {
        $requestId = 'requestId';

        $passthru = self::createMock(Passthru::class);
        $passthru->expects(self::once())->method('setTracedRequestsGetterCallback');
        $passthru->expects(self::once())->method('registerRequest')->with($requestId);
        $passthru->expects(self::atLeastOnce())
            ->method('passthru')
            ->willReturnCallback(function (ChunkInterface $chunk, AsyncContext $context): \Generator {
                yield $chunk;
            });

        $client = new ApiClient(
            new OptionsParser(),
            $passthru,
            new MockHttpClient()
        );

        $client->request('GET', 'https://example.com', [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $requestId,
            ],
        ]);
    }
}
