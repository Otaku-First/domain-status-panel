<?php

namespace App\Enums;

enum CheckResult: string
{
    case SUCCESS = 'SUCCESS';
    case FAIL = 'FAIL';
    case TIMEOUT = 'TIMEOUT';
    case DNS_ERROR = 'DNS_ERROR';
    case SSL_ERROR = 'SSL_ERROR';

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::FAIL => 'Failed',
            self::TIMEOUT => 'Timeout',
            self::DNS_ERROR => 'DNS Error',
            self::SSL_ERROR => 'SSL Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUCCESS => 'green',
            self::FAIL => 'red',
            self::TIMEOUT => 'orange',
            self::DNS_ERROR => 'purple',
            self::SSL_ERROR => 'yellow',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }
}
