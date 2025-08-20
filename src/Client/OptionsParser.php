<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Client;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 *
 * @phpstan-import-type UserDataOptions from ApiClientInterface
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 */
class OptionsParser
{
    /**
     * Find the request identifier from the "user_data" options
     * @param ApiClientOptions $options
     */
    public function getRequestId(?ResponseInterface $response = null, array $options = []): ?string
    {
        /** @var UserDataOptions $userData */
        $userData = $response?->getInfo('user_data') ?? $options['user_data'] ?? [];
        return $userData[ApiClientInterface::REQUEST_ID] ?? null;
    }
}
