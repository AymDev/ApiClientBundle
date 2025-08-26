<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\DependencyInjection\Compiler;

use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Client\ApiClient;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\DependencyInjection\AymdevApiClientExtension as Extension;
use AymDev\ApiClientBundle\Log\LogResponseProcessor;
use AymDev\ApiClientBundle\Log\RequestLogger;
use AymDev\ApiClientBundle\Passthru\Passthru;
use AymDev\ApiClientBundle\Validation\ValidationResponseProcessor;
use AymDev\ApiClientBundle\Validation\Validator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
class ApiClientPass implements CompilerPassInterface
{
    private const string ID_REQUEST_LOGGER = Extension::CONTAINER_PREFIX . '.request_logger';
    private const string ID_VALIDATOR = Extension::CONTAINER_PREFIX . '.validator';
    private const string ID_PROCESSOR_LOG = Extension::CONTAINER_PREFIX . '.passthru.processor.log';
    private const string ID_PROCESSOR_VALIDATION = Extension::CONTAINER_PREFIX . '.passthru.processor.validation';
    private const string ID_PASSTHRU = Extension::CONTAINER_PREFIX . '.passthru';
    private const string ID_CACHE_MANAGER = Extension::CONTAINER_PREFIX . '.cache.cache_manager';

    public function process(ContainerBuilder $container): void
    {
        $passthru = $this->registerPassthru($container);
        $cacheManager = $this->registerCacheManager($container);

        $clientDefinition = new Definition(ApiClient::class, [
            '$optionsParser' => new Reference(Extension::ID_CLIENT_OPTIONS_PARSER),
            '$passthru' => $passthru,
            '$cacheManager' => $cacheManager,
            '$httpClient' => new Reference(HttpClientInterface::class),
        ]);

        $container->setDefinition(Extension::CONTAINER_PREFIX . '.client', $clientDefinition);
        $container
            ->setDefinition(ApiClientInterface::class, $clientDefinition)
            ->setPublic(true)
        ;
    }

    private function registerPassthru(ContainerBuilder $container): Reference
    {
        $passthruDefinition = new Definition(Passthru::class);

        // Log response processor
        $logger = $container->getParameter(Extension::ID_LOGGER);
        if (is_string($logger)) {
            $container->setDefinition(self::ID_REQUEST_LOGGER, new Definition(RequestLogger::class, [
                '$logger' => new Reference($logger),
            ]));

            $container->setDefinition(self::ID_PROCESSOR_LOG, new Definition(LogResponseProcessor::class, [
                '$optionsParser' => new Reference(Extension::ID_CLIENT_OPTIONS_PARSER),
                '$requestLogger' => new Reference(self::ID_REQUEST_LOGGER),
            ]));

            $passthruDefinition->addMethodCall('registerResponseProcessor', [new Reference(self::ID_PROCESSOR_LOG)]);
        }

        // Validation response processor
        $container->setDefinition(self::ID_VALIDATOR, new Definition(Validator::class));

        $container->setDefinition(self::ID_PROCESSOR_VALIDATION, new Definition(ValidationResponseProcessor::class, [
            '$optionsParser' => new Reference(Extension::ID_CLIENT_OPTIONS_PARSER),
            '$validator' => new Reference(self::ID_VALIDATOR),
        ]));

        $passthruDefinition->addMethodCall('registerResponseProcessor', [
            new Reference(self::ID_PROCESSOR_VALIDATION)
        ]);

        $container->setDefinition(self::ID_PASSTHRU, $passthruDefinition);
        return new Reference(self::ID_PASSTHRU);
    }

    private function registerCacheManager(ContainerBuilder $container): ?Reference
    {
        $cachePool = $container->getParameter(Extension::ID_CACHE);
        if (!is_string($cachePool)) {
            return null;
        }

        $cachePool = new Reference($cachePool);
        $requestLogger = null === $container->getParameter(Extension::ID_LOGGER)
            ? null
            : new Reference(Extension::ID_LOGGER);

        $container->setDefinition(self::ID_CACHE_MANAGER, new Definition(CacheManager::class, [
            '$optionsParser' => new Reference(Extension::ID_CLIENT_OPTIONS_PARSER),
            '$cache' => $cachePool,
            '$requestLogger' => $requestLogger,
        ]));

        return new Reference(self::ID_CACHE_MANAGER);
    }
}
