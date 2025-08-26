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
 *     "aymdev.log_error_response_body"?: bool,
 *     "aymdev.validate_json"?: bool,
 *     "aymdev.validate_custom"?: callable(mixed $data): (null|string)
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

    /**
     * Request option used to ensure the response body is in JSON format.
     * Must be set to `true`.
     *
     * This option will disable caching on error.
     */
    public const string VALIDATE_JSON = 'aymdev.validate_json';

    /**
     * Request option used to validate the response JSON body.
     * Must be a function with the following signature where `$data` is the decoded JSON body and the optionally
     * returned string will be used as the exception message:
     * `callable(mixed[] $data): (null|string)`
     *
     * This option implies the `VALIDATE_JSON` option.
     */
    public const string VALIDATE_CALLBACK = 'aymdev.validate_custom';
}
