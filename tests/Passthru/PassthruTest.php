<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Passthru;

use AymDev\ApiClientBundle\Log\LogResponseProcessor;
use AymDev\ApiClientBundle\Passthru\ContextInterface;
use AymDev\ApiClientBundle\Passthru\Passthru;
use AymDev\ApiClientBundle\Passthru\ResponseProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ChunkInterface;

class PassthruTest extends TestCase
{
    public function testPassthru(): void
    {
        $passthru = null;
        $response = new MockResponse();
        $infos = [];
        $context = new AsyncContext($passthru, new MockHttpClient(), $response, $infos, null, 0);

        $chunk = self::createMock(ChunkInterface::class);
        $chunk->expects(self::once())->method('isLast')->willReturn(true);

        $processor = self::createMock(ResponseProcessorInterface::class);
        $processor->expects(self::once())->method('process')->with(self::isInstanceOf(ContextInterface::class));

        $passthru = new Passthru();
        $passthru->registerResponseProcessor($processor);

        $passthruGenerator = $passthru->passthru($chunk, $context);
        iterator_to_array($passthruGenerator);
    }

    public function testLogProcessorDecoration(): void
    {
        $callback = fn () => [];
        $requestId = 'requestId';

        $logProcessor = self::createMock(LogResponseProcessor::class);
        $logProcessor->expects(self::once())->method('setTracedRequestsGetterCallback')->with($callback);
        $logProcessor->expects(self::once())->method('registerRequest')->with($requestId);

        $passthru = new Passthru();

        // Add unrelated processor that will not be decorated
        $passthru->registerResponseProcessor(self::createMock(ResponseProcessorInterface::class));

        $passthru->registerResponseProcessor($logProcessor);
        $passthru->setTracedRequestsGetterCallback($callback);
        $passthru->registerRequest($requestId);
    }
}
