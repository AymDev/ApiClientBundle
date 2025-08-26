<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Validation;

/**
 * @internal
 */
class Validator
{
    private const string REQUEST_ID_PLACEHOLDER = '(unidentified request)';

    public function validateJson(string $responseBody, ?string $requestId): mixed
    {
        $requestId ??= self::REQUEST_ID_PLACEHOLDER;

        $data = json_decode($responseBody, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \UnexpectedValueException(vsprintf('The %s response body is not valid JSON: %s', [
                $requestId,
                json_last_error_msg(),
            ]));
        }

        return $data;
    }

    /**
     * @param callable(mixed $data): (null|string) $validator
     */
    public function validateCallback(string $responseBody, ?string $requestId, callable $validator): void
    {
        $requestId ??= self::REQUEST_ID_PLACEHOLDER;
        $data = $this->validateJson($responseBody, $requestId);

        if (null !== $message = call_user_func($validator, $data)) {
            throw new \UnexpectedValueException(vsprintf('The %s response is not valid: %s', [
                $requestId,
                $message
            ]));
        }
    }
}
