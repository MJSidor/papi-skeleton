<?php
declare(strict_types=1);

namespace papi\Resource\Field;

class Id extends Field
{
    public function getDefinition(): array
    {
        return [
            "INTEGER",
            "GENERATED ALWAYS AS IDENTITY",
            "PRIMARY KEY",
        ];
    }

    public function getPHPTypeName(): string
    {
        return 'integer';
    }
}