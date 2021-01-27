<?php
declare(strict_types=1);

namespace framework\Validator;

abstract class AbstractValidator
{
    abstract public function getValidationErrors(string $field, $data): ?string;
}