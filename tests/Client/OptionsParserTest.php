<?php

declare(strict_types=1);

namespace Tests\AymDevApiClientBundle\Client;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Client\OptionsParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OptionsParserTest extends TestCase
{
    /**
     * @param mixed[] $options
     */
    #[DataProvider('provideGetRequestIdCases')]
    public function testGetRequestId(?ResponseInterface $response, array $options, ?string $expected): void
    {
        $optionsParser = new OptionsParser();
        self::assertSame($expected, $optionsParser->getRequestId($response, $options));
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

        // From response (with precedence)
        $otherId = 'otherId';
        $response = new MockResponse(info: [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => $otherId,
            ],
        ]);
        yield [$response, $options, $otherId];
    }
}
