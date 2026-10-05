<?php

namespace App\Service;

use InvalidArgumentException;

final class ResultValidationException extends InvalidArgumentException
{
    public function __construct(
        public int $position,
        public string $field,
        string $message
    ) {
        parent::__construct($message);
    }
}