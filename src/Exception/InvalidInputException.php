<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Exception;

/**
 * The user passed an argument or option the command cannot work with.
 */
final class InvalidInputException extends \InvalidArgumentException {}
