<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\DependencyInjection\Compiler;

use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Client\ApiClient;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\DependencyInjection\AymdevApiClientExtension;
use AymDev\ApiClientBundle\Log\RequestLogger;
use AymDev\ApiClientBundle\Passthru\Passthru;
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

    private function registerPassthru(ContainerBuilder $container): Reference
    {
        $passthruDefinition = new Definition(Passthru::class);

        // Log response processor
        $logger = $container->getParameter(AymdevApiClientExtension::CONTAINER_PREFIX . '.logger');
        if (is_string($logger)) {
            $logger = new Reference($logger);

            $requestLoggerId = AymdevApiClientExtension::CONTAINER_PREFIX . '.passthru.processor.request_logger';
            $container->setDefinition($requestLoggerId, new Definition(RequestLogger::class, [
                '$logger' => $logger,
            ]));

            $passthruDefinition->addMethodCall('registerResponseProcessor', [new Reference($requestLoggerId)]);
        }

        $passthruId = AymdevApiClientExtension::CONTAINER_PREFIX . '.passthru';
        $container->setDefinition($passthruId, $passthruDefinition);

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
