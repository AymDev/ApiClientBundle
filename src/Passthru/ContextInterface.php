<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Passthru;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
interface ContextInterface
{
    public function getResponse(): ResponseInterface;

    public function getResponseBody(): ?string;

    public function getInfo(?string $key = null): mixed;
}