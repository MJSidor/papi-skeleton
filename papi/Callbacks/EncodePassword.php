<?php

declare(strict_types=1);

namespace papi\Callbacks;

use papi\Utils\PasswordEncoder;

class EncodePassword implements PreExecutionBodyModifier
{
    private string $fieldName;

    public function __construct(
        string $fieldName = 'password'
    ) {
        $this->fieldName = $fieldName;
    }

    public function modify(array &$body): void
    {
        if (isset($body['password']) === true) {
            $body[$this->fieldName] = PasswordEncoder::encodePassword($body['password']);
        }
    }
}
