<?php

namespace App\Services;

class MemberIdService
{
    public static function generate(string $name): string
    {
        $initials = strtoupper(substr($name, 0, 2));
        $code = rand(1000, 9999);
        return "SIPR26-{$initials}-{$code}";
    }
}
