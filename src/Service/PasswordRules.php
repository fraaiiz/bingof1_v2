<?php

namespace App\Service;

class PasswordRules
{
    public static function isComplex(string $password): bool
    {
        return mb_strlen($password) >= 8
            && preg_match('/\p{Ll}/u', $password) === 1
            && preg_match('/\p{Lu}/u', $password) === 1
            && preg_match('/\p{N}/u', $password) === 1
            && preg_match('/[^\p{L}\p{N}]/u', $password) === 1;
    }
}
