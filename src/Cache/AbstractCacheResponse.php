<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Cache;

use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractCacheResponse implements ResponseInterface
{
    /**
     * @throws ServerException|ClientException|RedirectionException
     * @throws TransportExceptionInterface
     */
    protected function checkStatusCode(): void
    {
        if (500 <= $this->getStatusCode()) {
            throw new ServerException($this);
        }

        if (400 <= $this->getStatusCode()) {
            throw new ClientException($this);
        }

        if (300 <= $this->getStatusCode()) {
            throw new RedirectionException($this);
        }
    }
}
