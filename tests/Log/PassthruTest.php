<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Log\Passthru;
use AymDev\ApiClientBundle\Log\RequestLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ChunkInterface;

class PassthruTest extends TestCase
{
    public function testPassthru(): void
    {
        // HttpClient mocks
        $chunk = self::createMock(ChunkInterface::class);
        $chunk->expects(self::once())->method('isLast')->willReturn(true);

        $passthru = null;
        $response = new MockResponse();
        $infos = [];
        $context = new AsyncContext($passthru, new MockHttpClient(), $response, $infos, null, 0);

        // Ensure request ID is parsed
        $requestId = 'requestId';
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::atLeastOnce())->method('getRequestId')->willReturn($requestId);

        // Logger must be called
        $requestLogger = self::createMock(RequestLogger::class);
        $requestLogger->expects(self::once())
            ->method('logRequest')
            ->with(
                self::isFloat(),
                $context
            );

        // Set up traced requests
        $passthru = new Passthru($optionsParser, $requestLogger);
        $passthru->setTracedRequestsGetterCallback(fn () => [
            [
                'options' => [
                    'user_data' => [
                        ApiClientInterface::REQUEST_ID => $requestId,
                    ],
                ],
            ]
        ]);

        // DO register request before execution
        $passthru->registerRequest($requestId);

        iterator_to_array($passthru->passthru($chunk, $context));
    }

    public function testDoNothingWhenRequestIsNotRegistered(): void
    {
        // HttpClient mocks
        $chunk = self::createMock(ChunkInterface::class);
        $chunk->expects(self::once())->method('isLast')->willReturn(true);

        $passthru = null;
        $response = new MockResponse();
        $infos = [];
        $context = new AsyncContext($passthru, new MockHttpClient(), $response, $infos, null, 0);

        // Ensure request ID is parsed
        $requestId = 'requestId';
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::atLeastOnce())->method('getRequestId')->willReturn($requestId);

        // Logger is not called
        $requestLogger = self::createMock(RequestLogger::class);
        $requestLogger->expects(self::never())->method('logRequest');

        // Set up traced requests
        $passthru = new Passthru($optionsParser, $requestLogger);
        $passthru->setTracedRequestsGetterCallback(fn () => [
            [
                'options' => [
                    'user_data' => [
                        ApiClientInterface::REQUEST_ID => $requestId,
                    ],
                ],
            ]
        ]);

        // DO NOT register request before execution (no request ID)

        iterator_to_array($passthru->passthru($chunk, $context));
    }
}
