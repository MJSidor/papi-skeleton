<?php
declare(strict_types=1);

namespace papi\Database;

use config\DatabaseConfig;
use Medoo\Medoo;

class MedooHandler
{
    public static function getDbHandler(): Medoo
    {
        return new Medoo(
            DatabaseConfig::getConfig()
        );
    }
}