<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('aymdev_api_client');
        $rootNode = $treeBuilder->getRootNode();

        if (!$rootNode instanceof ArrayNodeDefinition) {
            throw new \RuntimeException(sprintf(
                'The configuration root node must be an instance of "%s".',
                ArrayNodeDefinition::class
            ));
        }

        $startingPoint = $rootNode->children();

        $startingPoint->scalarNode('logger')->defaultNull()->end();
        $startingPoint->scalarNode('cache')->defaultNull()->end();

        return $treeBuilder;
    }
}
