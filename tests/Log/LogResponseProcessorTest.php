<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Log;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Log\LogResponseProcessor;
use AymDev\ApiClientBundle\Log\RequestLogger;
use AymDev\ApiClientBundle\Passthru\ContextInterface;
use PHPUnit\Framework\TestCase;

class LogResponseProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $context = self::createMock(ContextInterface::class);

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
        $processor = new LogResponseProcessor($optionsParser, $requestLogger);
        $processor->setTracedRequestsGetterCallback(fn () => [
            [
                'options' => [
                    'user_data' => [
                        ApiClientInterface::REQUEST_ID => $requestId,
                    ],
                ],
            ]
        ]);

        // DO register request before execution
        $processor->registerRequest($requestId);

        $processor->process($context);
    }

    public function testDoNothingWhenRequestIsNotRegistered(): void
    {
        $context = self::createMock(ContextInterface::class);

        // Ensure request ID is parsed
        $requestId = 'requestId';
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::atLeastOnce())->method('getRequestId')->willReturn($requestId);

        // Logger is not called
        $requestLogger = self::createMock(RequestLogger::class);
        $requestLogger->expects(self::never())->method('logRequest');

        // Set up traced requests
        $processor = new LogResponseProcessor($optionsParser, $requestLogger);
        $processor->setTracedRequestsGetterCallback(fn () => [
            [
                'options' => [
                    'user_data' => [
                        ApiClientInterface::REQUEST_ID => $requestId,
                    ],
                ],
            ]
        ]);

        // DO NOT register request before execution (no request ID)

        $processor->process($context);
    }
}
