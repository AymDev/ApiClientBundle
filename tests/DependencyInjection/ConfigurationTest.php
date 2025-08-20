<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\DependencyInjection;

use AymDev\ApiClientBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends TestCase
{
    /**
     * @param mixed[] $parsedYamlConfig
     */
    #[DataProvider('provideConfiguration')]
    public function testConfigurationFormat(array $parsedYamlConfig): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $processedConfiguration = $processor->processConfiguration($configuration, $parsedYamlConfig);

        self::assertArrayHasKey('logger', $processedConfiguration);
        self::assertArrayHasKey('cache', $processedConfiguration);
    }

    /**
     * @return \Generator<mixed[]>
     */
    public static function provideConfiguration(): \Generator
    {
        $configFiles = Finder::create()
            ->in(__DIR__ . '/../fixtures/configuration')
            ->files()
            ->name(['*.yaml', '*.yml'])
        ;

        foreach ($configFiles as $file) {
            yield [Yaml::parseFile($file->getRealPath())];
        }
    }
}
