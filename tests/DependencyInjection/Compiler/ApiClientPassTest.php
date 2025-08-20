<?php

declare(strict_types=1);

namespace Tests\AymDevApiClientBundle\DependencyInjection\Compiler;

use AymDev\ApiClientBundle\AymdevApiClientBundle;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class ApiClientPassTest extends TestCase
{
    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(AymDevApiClientTestKernel::KERNEL_CACHE_DIR);
        parent::tearDown();
    }

    public function testClientDefinition(): void
    {
        $kernel = new AymDevApiClientTestKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        self::assertTrue($container->has(ApiClientInterface::class));
    }
}

class AymDevApiClientTestKernel extends Kernel
{
    public const string KERNEL_CACHE_DIR = __DIR__ . '/../../cache';

    public function __construct(
        /** @var array<string, mixed> */
        private readonly array $aymdevApiClientConfig = []
    ) {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new AymdevApiClientBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('aymdev_api_client', $this->aymdevApiClientConfig);

            // Parameter is undefined but required
            $container->setParameter('kernel.secret', '$ecret');

            // Makes the test.service_container service available
            $container->prependExtensionConfig('framework', ['test' => true]);
        });
    }

    public function getCacheDir(): string
    {
        return self::KERNEL_CACHE_DIR . '/' . spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return $this->getCacheDir();
    }
}
