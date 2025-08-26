<?php

declare(strict_types=1);

namespace AymDev\ApiClientBundle\Passthru;

/**
 * @internal
 */
interface ResponseProcessorInterface
{
    public function process(ContextInterface $context): void;
}