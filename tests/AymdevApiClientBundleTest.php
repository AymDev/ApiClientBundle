<?php

declare(strict_types=1);

namespace Tests\AymDevApiClientBundle;

use AymDev\ApiClientBundle\AymdevApiClientBundle;
use AymDev\ApiClientBundle\DependencyInjection\AymdevApiClientExtension;
use PHPUnit\Framework\TestCase;

class AymdevApiClientBundleTest extends TestCase
{
    /**
     * Ensure the extension will be loaded
     */
    public function testExtensionNameConsistency(): void
    {
        $bundle = new AymdevApiClientBundle();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(AymdevApiClientExtension::class, $extension);
    }
}
