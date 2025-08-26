<?php

declare(strict_types=1);

namespace Tests\AymDev\ApiClientBundle\DependencyInjection\Compiler;

use AymDev\ApiClientBundle\AymdevApiClientBundle;
use AymDev\ApiClientBundle\Cache\CacheManager;
use AymDev\ApiClientBundle\Client\ApiClientInterface;
use AymDev\ApiClientBundle\Log\LogResponseProcessor;
use AymDev\ApiClientBundle\Log\RequestLogger;
use AymDev\ApiClientBundle\Passthru\Passthru;
use AymDev\ApiClientBundle\Validation\ValidationResponseProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        self::assertInstanceOf(ApiClientInterface::class, $container->get(ApiClientInterface::class));
        self::assertInstanceOf(Passthru::class, $container->get('aymdev_api_client.passthru'));
        self::assertInstanceOf(
            ValidationResponseProcessor::class,
            $container->get('aymdev_api_client.passthru.processor.validation')
        );

        self::assertFalse($container->has('aymdev_api_client.passthru.processor.log'));
        self::assertFalse($container->has('aymdev_api_client.cache.cache_manager'));
    }

    public function testLogServicesDefinition(): void
    {
        $kernel = new AymDevApiClientTestKernel([
            'logger' => 'logger',
        ]);
        $kernel->boot();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        self::assertInstanceOf(RequestLogger::class, $container->get('aymdev_api_client.request_logger'));
        self::assertInstanceOf(
            LogResponseProcessor::class,
            $container->get('aymdev_api_client.passthru.processor.log')
        );
    }

    public function testCacheServicesDefinition(): void
    {
        $kernel = new AymDevApiClientTestKernel([
            'cache' => 'cache.app',
        ]);
        $kernel->boot();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        self::assertInstanceOf(CacheManager::class, $container->get('aymdev_api_client.cache.cache_manager'));
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
