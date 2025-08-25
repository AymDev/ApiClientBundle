<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Client;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @phpstan-import-type ApiClientOptions from ApiClientInterface
 */
class OptionsParserTest extends TestCase
{
    /**
     * @param ApiClientOptions $options
     */
    #[DataProvider('provideGetRequestIdCases')]
    public function testGetRequestId(?ResponseInterface $response, array $options, ?string $expected): void
    {
        $optionsParser = new OptionsParser();
        self::assertSame($expected, $optionsParser->getRequestId($response ?? $options));
    }

    public static function provideGetRequestIdCases(): \Generator
    {
        // Nothing supplied
        yield [null, [], null];

        // From options
        $requestId = 'requestId';
        $options = [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $requestId,
            ],
        ];
        yield [null, $options, $requestId];

        // From response
        $otherId = 'otherId';
        $response = new MockResponse(info: [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $otherId,
            ],
        ]);
        yield [$response, [], $otherId];
    }

    /**
     * @param ApiClientOptions $options
     */
    #[DataProvider('provideHasCacheOptionsCases')]
    public function testHasCacheOptions(array $options, bool $expected): void
    {
        $optionsParser = new OptionsParser();
        self::assertSame($expected, $optionsParser->hasCacheOptions($options));
    }

    public static function provideHasCacheOptionsCases(): \Generator
    {
        // Missing request ID
        yield [
            [
                'user_data' => [
                    ApiClientInterface::CACHE_DURATION => 60,
                ],
            ],
            false,
        ];

        // Valid cache duration
        yield [
            [
                'user_data' => [
                    ApiClientInterface::REQUEST_ID => 'requestId',
                    ApiClientInterface::CACHE_DURATION => 60,
                ],
            ],
            true,
        ];

        // Invalid cache duration
        yield [
            [
                'user_data' => [
                    ApiClientInterface::REQUEST_ID => 'requestId',
                    ApiClientInterface::CACHE_DURATION => [],
                ],
            ],
            false,
        ];

        // Valid cache expiration
        yield [
            [
                'user_data' => [
                    ApiClientInterface::REQUEST_ID => 'requestId',
                    ApiClientInterface::CACHE_EXPIRATION => new \DateTime(),
                ],
            ],
            true,
        ];

        // Invalid cache expiration
        yield [
            [
                'user_data' => [
                    ApiClientInterface::REQUEST_ID => 'requestId',
                    ApiClientInterface::CACHE_EXPIRATION => [],
                ],
            ],
            false,
        ];

        // Valid error cache duration
        yield [
            [
                'user_data' => [
                    ApiClientInterface::REQUEST_ID => 'requestId',
                    ApiClientInterface::CACHE_ERROR_DURATION => 60,
                ],
            ],
            true,
        ];

        // Invalid error cache duration
        yield [
            [
                'user_data' => [
                    ApiClientInterface::REQUEST_ID => 'requestId',
                    ApiClientInterface::CACHE_ERROR_DURATION => [],
                ],
            ],
            false,
        ];
    }
}
