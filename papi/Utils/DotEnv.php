<?php

declare(strict_types=1);

namespace papi\Utils;

use InvalidArgumentException;
use RuntimeException;

/**
 * Loads PHP environment variables from .env files
 */
class DotEnv
{
    /**
     * Load environment variables from .env file
     *
     * @param string $path
     */
    public static function load(string $path = '.env'): void
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("%$path does not exist");
        }
        if (! is_readable($path)) {
            throw new RuntimeException("$path is not readable");
        }

        if (($lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === false) {
            return;
        }

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            putenv("$name=$value");
        }
    }
}
