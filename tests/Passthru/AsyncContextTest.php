<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Passthru;

use AymDev\ApiClientBundle\Passthru\AsyncContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext as SymfonyAsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;

class AsyncContextTest extends TestCase
{
    public function testGetResponse(): void
    {
        $passthru = null;
        $response = new MockResponse();
        $infos = [];
        $context = new SymfonyAsyncContext($passthru, new MockHttpClient(), $response, $infos, null, 0);

        $decorator = new AsyncContext($context);
        self::assertSame($response, $decorator->getResponse());
    }

    public function testGetResponseBody(): void
    {
        $responseBody = 'body';

        // Define context resource used by context
        $resource = fopen('php://temp', 'w+');
        if (false === $resource) {
            throw new \RuntimeException('Unable to open temp file');
        }
        fwrite($resource, $responseBody);

        $passthru = null;
        $response = new MockResponse();
        $infos = [];
        $context = new SymfonyAsyncContext($passthru, new MockHttpClient(), $response, $infos, $resource, 0);

        $decorator = new AsyncContext($context);
        self::assertSame($responseBody, $decorator->getResponseBody());
        fclose($resource);
    }

    public function testGetInfo(): void
    {
        $passthru = null;
        $response = new MockResponse();
        $infos = [
            'user_data' => [
                'key' => 'value',
            ],
        ];
        $context = new SymfonyAsyncContext($passthru, new MockHttpClient(), $response, $infos, null, 0);

        $decorator = new AsyncContext($context);
        $infosResult = $decorator->getInfo();
        self::assertIsArray($infosResult);
        self::assertArrayHasKey('user_data', $infosResult);
        self::assertIsArray($infosResult['user_data']);
        self::assertArrayHasKey('key', $infosResult['user_data']);
        self::assertSame('value', $infosResult['user_data']['key']);
    }
}
