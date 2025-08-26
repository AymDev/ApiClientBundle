<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\Validation;

use AymDev\ApiClientBundle\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidateValidJson(): void
    {
        $json = '{"key": "value"}';

        $validator = new Validator();
        $data = $validator->validateJson($json, null);

        self::assertIsArray($data);
        self::assertArrayHasKey('key', $data);
        self::assertSame('value', $data['key']);
    }

    public function testValidateInvalidJson(): void
    {
        self::expectException(\UnexpectedValueException::class);
        $json = '<invalid json>';

        $validator = new Validator();
        $validator->validateJson($json, null);
    }

    public function testValidateCallback(): void
    {
        $json = '{"key": "value"}';

        $validator = new Validator();
        $validator->validateCallback($json, null, function (mixed $data): ?string {
            self::assertIsArray($data);
            self::assertArrayHasKey('key', $data);
            self::assertSame('value', $data['key']);
            return null;
        });
    }

    public function testValidateCallbackWithException(): void
    {
        self::expectException(\UnexpectedValueException::class);
        $json = '{"key": "value"}';

        $validator = new Validator();
        $validator->validateCallback($json, null, fn(mixed $data) => 'exception message');
    }

    public function testValidateCallbackWithInvalidJson(): void
    {
        self::expectException(\UnexpectedValueException::class);
        $json = '<invalid json>';

        $validator = new Validator();
        $validator->validateCallback($json, null, fn(mixed $data) => null);
    }
}
