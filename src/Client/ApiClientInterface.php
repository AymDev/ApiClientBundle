<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-type UserDataOptions array{
 *     "aymdev.request_id"?: string
 * }
 *
 * @phpstan-type ApiClientOptions mixed[]|array{
 *      user_data?: UserDataOptions
 *  }
 */
interface ApiClientInterface extends HttpClientInterface
{
    /**
     * Request option used to log the request.
     * Must be a unique string.
     */
    public const REQUEST_ID = 'aymdev.request_id';
}
