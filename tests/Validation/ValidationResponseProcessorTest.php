<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Validation;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Passthru\ContextInterface;
use AymDev\ApiClientBundle\Validation\ValidationResponseProcessor;
use AymDev\ApiClientBundle\Validation\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;

class ValidationResponseProcessorTest extends TestCase
{
    public function testValidateCallback(): void
    {
        $callback = fn (array $data) => null;
        $responseBody = 'body';

        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('needsValidation')->willReturn(true);
        $optionsParser->expects(self::once())->method('getUserData')->willReturn([
            ApiClientInterface::VALIDATE_CALLBACK => $callback,
        ]);

        $validator = self::createMock(Validator::class);
        $validator->expects(self::once())
            ->method('validateCallback')
            ->with($responseBody, self::anything(), $callback);

        $context = self::createMock(ContextInterface::class);
        $context->expects(self::once())->method('getResponseBody')->willReturn($responseBody);
        $context->expects(self::atLeastOnce())->method('getResponse')->willReturn(new MockResponse());

        $processor = new ValidationResponseProcessor($optionsParser, $validator);
        $processor->process($context);
    }

    public function testValidateJson(): void
    {
        $responseBody = 'body';

        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('needsValidation')->willReturn(true);
        $optionsParser->expects(self::once())->method('getUserData')->willReturn([
            ApiClientInterface::VALIDATE_JSON => true,
        ]);

        $validator = self::createMock(Validator::class);
        $validator->expects(self::once())
            ->method('validateJson')
            ->with($responseBody, self::anything());

        $context = self::createMock(ContextInterface::class);
        $context->expects(self::once())->method('getResponseBody')->willReturn($responseBody);
        $context->expects(self::atLeastOnce())->method('getResponse')->willReturn(new MockResponse());

        $processor = new ValidationResponseProcessor($optionsParser, $validator);
        $processor->process($context);
    }

    public function testNoValidationOptions(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('needsValidation')->willReturn(false);
        $optionsParser->expects(self::never())->method('getUserData');

        $validator = self::createMock(Validator::class);
        $validator->expects(self::never())->method('validateCallback');
        $validator->expects(self::never())->method('validateJson');

        $processor = new ValidationResponseProcessor($optionsParser, $validator);
        $processor->process(self::createMock(ContextInterface::class));
    }

    public function testNoResponseBody(): void
    {
        $optionsParser = self::createMock(OptionsParser::class);
        $optionsParser->expects(self::once())->method('needsValidation')->willReturn(true);
        $optionsParser->expects(self::never())->method('getUserData');

        $validator = self::createMock(Validator::class);
        $validator->expects(self::never())->method('validateCallback');
        $validator->expects(self::never())->method('validateJson');

        $context = self::createMock(ContextInterface::class);
        $context->expects(self::once())->method('getResponseBody')->willReturn(null);

        $processor = new ValidationResponseProcessor($optionsParser, $validator);
        $processor->process($context);
    }
}
