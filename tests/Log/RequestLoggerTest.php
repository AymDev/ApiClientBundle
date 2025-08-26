<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Cache\CachedResponse;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Log\RequestLogger;
use AymDev\ApiClientBundle\Passthru\ContextInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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

        // Set request/response body that should not be logged
        $request = [
            'body' => 'content',
            'json' => [
                'key' => 'value',
            ],
        ];
        $responseBody = 'response';

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method($logMethod)
            ->with(
                self::isString(),
                [
                    'method' => $method,
                    'url' => $url,
                    'request_body' => null,
                    'response_status' => $status,
                    'response_body' => null,
                    'time' => $duration,
                    'cache' => false,
                    'error' => $error,
                ]
            );

        $response = new MockResponse($responseBody, [
            'http_method' => $method,
            'url' => $url,
            'http_code' => $status,
            'error' => $error,
        ]);
        $context = self::createMock(ContextInterface::class);
        $context->expects(self::atLeastOnce())
            ->method('getInfo')
            ->willReturnCallback(fn (string $key) => $response->getInfo($key));

        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest($duration, $context, $request);
    }

    public static function provideResponses(): \Generator
    {
        yield [204, 'info'];
        yield [404, 'error'];
    }

    public function testLogCachedCall(): void
    {
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::isString(),
                self::callback(fn(array $context) => $context['cache'] === true),
            );

        $response = self::createMock(CachedResponse::class);
        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest(1, $response, []);
    }

    public function testLogRequestBody(): void
    {
        $requestBody = 'content';
        $request = [
            'body' => $requestBody,
            'user_data' => [
                ApiClientInterface::LOG_REQUEST_BODY => true,
            ],
        ];

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::isString(),
                self::callback(fn(array $context) => $context['request_body'] === $requestBody),
            );

        $response = self::createMock(CachedResponse::class);
        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest(1, $response, $request);
    }

    public function testLogJsonRequestBody(): void
    {
        $jsonBody = '{"key":"value"}';
        $request = [
            'json' => [
                'key' => 'value'
            ],
            'user_data' => [
                ApiClientInterface::LOG_REQUEST_BODY => true,
            ],
        ];

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::isString(),
                self::callback(fn(array $context) => $context['request_body'] === $jsonBody),
            );

        $response = self::createMock(CachedResponse::class);
        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest(1, $response, $request);
    }

    public function testLogResponseBodyFromCachedResponse(): void
    {
        $responseBody = 'content';
        $response = new CachedResponse(200, [], $responseBody, [], []);

        $request = [
            'user_data' => [
                ApiClientInterface::LOG_RESPONSE_BODY => true,
            ],
        ];

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::isString(),
                self::callback(fn(array $context) => $context['response_body'] === $responseBody)
            );

        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest(1, $response, $request);
    }

    public function testLogResponseJsonBodyFromCachedResponse(): void
    {
        $jsonBody = '{"key":"value"}';
        $jsonData = [
            'key' => 'value',
        ];
        $response = new CachedResponse(200, [], '', $jsonData, []);

        $request = [
            'user_data' => [
                ApiClientInterface::LOG_RESPONSE_BODY => true,
            ],
        ];

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::isString(),
                self::callback(fn(array $context) => $context['response_body'] === $jsonBody)
            );

        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest(1, $response, $request);
    }

    public function testLogResponseBodyFromContext(): void
    {
        $responseBody = 'content';
        $request = [
            'user_data' => [
                ApiClientInterface::LOG_RESPONSE_BODY => true,
            ],
        ];

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::isString(),
                self::callback(fn(array $context) => $context['response_body'] === $responseBody)
            );

        $context = self::createMock(ContextInterface::class);
        $context->expects(self::once())->method('getResponseBody')->willReturn($responseBody);

        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest(1, $context, $request);
    }

    #[DataProvider('provideLogErrorResponseBodyCases')]
    public function testNotLogErrorResponseBodyWithoutError(int $statusCode, bool $expectsLog): void
    {
        $responseBody = 'content';
        $response = new CachedResponse($statusCode, [], $responseBody, [], []);

        $request = [
            'user_data' => [
                ApiClientInterface::LOG_ERROR_RESPONSE_BODY => true,
            ],
        ];

        $expectedLog = $expectsLog ? $responseBody : null;
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                self::isString(),
                self::callback(fn(array $context) => $context['response_body'] === $expectedLog),
            );

        $requestLogger = new RequestLogger($logger);
        $requestLogger->logRequest(1, $response, $request);
    }

    public static function provideLogErrorResponseBodyCases(): \Generator
    {
        yield [200, false];
        yield [404, true];
    }
}
