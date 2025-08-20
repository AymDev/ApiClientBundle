<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

require_once __DIR__ . '/../vendor/autoload.php';

// Disable error handling conflict between Symfony & PHPUnit
// @see https://github.com/symfony/symfony/issues/53812
set_exception_handler([new ErrorHandler(), 'handleException']);
