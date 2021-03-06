<?php

declare(strict_types=1);

namespace papi\Exception;

use Exception;

/**
 * Exception thrown if requested feature has not been implemented yet
 */
class NotImplementedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Feature has not been implemented yet!');
    }
}
