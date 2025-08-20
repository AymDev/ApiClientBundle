<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\DependencyInjection;

use AymDev\ApiClientBundle\Client\OptionsParser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * @internal
 */
final class AymdevApiClientExtension extends Extension
{
    public const string CONTAINER_PREFIX = 'aymdev_api_client';
    public const string ID_CLIENT_OPTIONS_PARSER = self::CONTAINER_PREFIX . '.client.options_parser';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Dependencies managed in compiler pass
        $logger = is_string($config['logger']) ? $config['logger'] : null;
        $container->setParameter(self::CONTAINER_PREFIX . '.logger', $logger);

        // Default helper services
        $container->setDefinition(self::ID_CLIENT_OPTIONS_PARSER, new Definition(OptionsParser::class));
    }
}
