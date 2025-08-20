<?php

declare(strict_types=1);

namespace Tests\AymDevApiClientBundle\Log;

use AymDev\ApiClientBundle\Log\RequestLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;

class RequestLoggerTest extends TestCase
{
    /**
     * @param non-empty-string $logMethod
     */
    #[DataProvider('provideResponses')]
    public function testLogRequest(int $status, string $logMethod): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $duration = 42.42;
        $error = 'OK';

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method($logMethod)
            ->with(
                self::isString(),
                [
                    'method' => $method,
                    'url' => $url,
                    'response_status' => $status,
                    'time' => $duration,
                    'error' => $error,
                ]
            );

        $passthru = null;
        $response = new MockResponse(info: [
            'http_method' => $method,
            'url' => $url,
            'http_code' => $status,
            'error' => $error,
        ]);
        $infos = [];
        $context = new AsyncContext($passthru, new MockHttpClient(), $response, $infos, null, 0);
        $request = [
            'method' => $method,
            'url' => $url,
            'options' => [],
        ];

        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest($duration, $context, $request);
    }

    public static function provideResponses(): \Generator
    {
        yield [204, 'info'];
        yield [404, 'error'];
    }
}
