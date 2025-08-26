<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Validation;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use AymDev\ApiClientBundle\Passthru\ContextInterface;
use AymDev\ApiClientBundle\Passthru\ResponseProcessorInterface;

/**
 * @internal
 */
class ValidationResponseProcessor implements ResponseProcessorInterface
{
    public function __construct(
        private readonly OptionsParser $optionsParser,
        private readonly Validator $validator,
    ) {
    }

    public function process(ContextInterface $context): void
    {
        if (!$this->optionsParser->needsValidation($context->getResponse())) {
            return;
        }

        if (null === $responseBody = $context->getResponseBody()) {
            return;
        }

        $userData = $this->optionsParser->getUserData($context->getResponse());

        if (isset($userData[ApiClientInterface::VALIDATE_CALLBACK])) {
            $this->validator->validateCallback(
                $responseBody,
                $this->optionsParser->getRequestId($context->getResponse()),
                $userData[ApiClientInterface::VALIDATE_CALLBACK]
            );
            return;
        }

        if (isset($userData[ApiClientInterface::VALIDATE_JSON])) {
            $this->validator->validateJson(
                $responseBody,
                $this->optionsParser->getRequestId($context->getResponse()),
            );
        }
    }
}
