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
     * @param null|ResponseInterface|ApiClientOptions $responseOrOptions
     */
    public function getRequestId(null|ResponseInterface|array $responseOrOptions): ?string
    {
        $userData = $this->getUserData($responseOrOptions);
        return $userData[ApiClientInterface::REQUEST_ID] ?? null;
    }

    /**
     * Detect caching options from the "user_data" options
     * @param null|ResponseInterface|ApiClientOptions $responseOrOptions
     */
    public function hasCacheOptions(null|ResponseInterface|array $responseOrOptions): bool
    {
        if (null === $this->getRequestId($responseOrOptions)) {
            return false;
        }

        $userData = $this->getUserData($responseOrOptions);
        return is_int($userData[ApiClientInterface::CACHE_DURATION] ?? null)
            || ($userData[ApiClientInterface::CACHE_EXPIRATION] ?? null) instanceof \DateTime
            || is_int($userData[ApiClientInterface::CACHE_ERROR_DURATION] ?? null);
    }

    /**
     * @param null|ResponseInterface|ApiClientOptions $responseOrOptions
     * @return UserDataOptions
     */
    private function getUserData(null|ResponseInterface|array $responseOrOptions): array
    {
        /** @var UserDataOptions $userData */
        $userData = $responseOrOptions instanceof ResponseInterface
            ? $responseOrOptions->getInfo('user_data')
            : ($responseOrOptions['user_data'] ?? []);
        return $userData;
    }
}
