<?php

namespace App\Service;

class PagePath
{
    public static function internal(mixed $value): ?string
    {
        if (!is_string($value) || $value === '' || strlen($value) > 255) {
            return null;
        }

        if (preg_match('#\A/(?!/)[A-Za-z0-9._~/-]*\z#', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
