<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-type UserDataOptions array{
 *     "aymdev.request_id"?: string,
 *     "aymdev.cache_ttl"?: int,
 *     "aymdev.cache_expiration"?: \DateTimeInterface,
 *     "aymdev.cache_error_ttl"?: int,
 *     "aymdev.log_request_body"?: bool,
 *     "aymdev.log_response_body"?: bool,
 *     "aymdev.log_error_response_body"?: bool
 * }
 *
 * @phpstan-type ApiClientOptions mixed[]&array{
 *     body?: mixed,
 *     json?: mixed[],
 *     user_data?: UserDataOptions
 * }
 */
interface ApiClientInterface extends HttpClientInterface
{
    /**
     * Request option used to log the request.
     * Must be a unique string.
     */
    public const string REQUEST_ID = 'aymdev.request_id';

    /**
     * Request option used to cache the response.
     * The value is the duration in seconds.
     */
    public const string CACHE_DURATION = 'aymdev.cache_ttl';

    /**
     * Request option used to cache the response.
     * The value is the expiration as a DateTime object.
     */
    public const string CACHE_EXPIRATION = 'aymdev.cache_expiration';

    /**
     * Request option used to cache the response if it fails (HTTP status 4xx|5xx).
     * The value is the duration in seconds.
     */
    public const string CACHE_ERROR_DURATION = 'aymdev.cache_error_ttl';

    /**
     * Request option used to log the request body.
     * Must be set to `true`.
     */
    public const string LOG_REQUEST_BODY = 'aymdev.log_request_body';

    /**
     * Request option used to log the response body.
     * Must be set to `true`.
     */
    public const string LOG_RESPONSE_BODY = 'aymdev.log_response_body';

    /**
     * Request option used to log the response body only on error.
     * Must be set to `true`.
     */
    public const string LOG_ERROR_RESPONSE_BODY = 'aymdev.log_error_response_body';
}
