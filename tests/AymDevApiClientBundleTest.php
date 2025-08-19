<?php

declare(strict_types=1);

namespace Tests\AymDevApiClientBundle;

use AymDev\ApiClientBundle\AymDevApiClientBundle;
use AymDev\ApiClientBundle\DependencyInjection\AymDevApiClientExtension;
use PHPUnit\Framework\TestCase;

class AymDevApiClientBundleTest extends TestCase
{
    /**
     * Ensure the extension will be loaded
     */
    public function testExtensionNameConsistency(): void
    {
        $bundle = new AymDevApiClientBundle();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(AymDevApiClientExtension::class, $extension);
    }
}
