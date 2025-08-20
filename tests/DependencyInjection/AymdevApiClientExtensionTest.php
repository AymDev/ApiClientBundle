<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\DependencyInjection;

use AymDev\ApiClientBundle\DependencyInjection\AymdevApiClientExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AymdevApiClientExtensionTest extends TestCase
{
    public function testDefaultDefinitions(): void
    {
        $container = new ContainerBuilder();
        $extension = new AymdevApiClientExtension();

        $extension->load([], $container);

        // Container parameters
        self::assertTrue($container->hasParameter('aymdev_api_client.logger'));
        self::assertTrue($container->hasParameter('aymdev_api_client.cache'));

        // Helper services
        self::assertTrue($container->hasDefinition('aymdev_api_client.client.options_parser'));
    }
}
