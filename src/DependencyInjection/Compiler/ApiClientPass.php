<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\DependencyInjection\Compiler;

use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Client\ApiClient;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\DependencyInjection\AymdevApiClientExtension;
use AymDev\ApiClientBundle\Log\Passthru;
use AymDev\ApiClientBundle\Log\RequestLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $passthru = $this->registerPassthru($container);
        $cacheManager = $this->registerCacheManager($container);

        $clientDefinition = new Definition(ApiClient::class, [
            '$optionsParser' => new Reference(AymdevApiClientExtension::ID_CLIENT_OPTIONS_PARSER),
            '$passthru' => $passthru,
            '$cacheManager' => $cacheManager,
            '$httpClient' => new Reference(HttpClientInterface::class),
        ]);

        $container->setDefinition(AymdevApiClientExtension::CONTAINER_PREFIX . '.client', $clientDefinition);
        $container
            ->setDefinition(ApiClientInterface::class, $clientDefinition)
            ->setPublic(true)
        ;
    }

    private function registerPassthru(ContainerBuilder $container): ?Reference
    {
        $logger = $container->getParameter(AymdevApiClientExtension::CONTAINER_PREFIX . '.logger');
        if (!is_string($logger)) {
            return null;
        }

        $logger = new Reference($logger);

        $requestLoggerId = AymdevApiClientExtension::CONTAINER_PREFIX . '.log.request_logger';
        $container->setDefinition($requestLoggerId, new Definition(RequestLogger::class, [
            '$logger' => $logger,
        ]));

        $passthruId = AymdevApiClientExtension::CONTAINER_PREFIX . '.log.passthru';
        $container->setDefinition(
            $passthruId,
            new Definition(Passthru::class, [
                '$optionsParser' => new Reference(AymdevApiClientExtension::ID_CLIENT_OPTIONS_PARSER),
                '$requestLogger' => new Reference($requestLoggerId),
            ]),
        );

        return new Reference($passthruId);
    }

    private function registerCacheManager(ContainerBuilder $container): ?Reference
    {
        $cachePool = $container->getParameter(AymdevApiClientExtension::CONTAINER_PREFIX . '.cache');
        if (!is_string($cachePool)) {
            return null;
        }

        $cachePool = new Reference($cachePool);

        $cacheManagerId = AymdevApiClientExtension::CONTAINER_PREFIX . '.cache.cache_manager';
        $container->setDefinition($cacheManagerId, new Definition(CacheManager::class, [
            '$optionsParser' => new Reference(AymdevApiClientExtension::ID_CLIENT_OPTIONS_PARSER),
            '$cache' => $cachePool,
        ]));

        return new Reference($cacheManagerId);
    }
}
