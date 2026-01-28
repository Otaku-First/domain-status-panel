<?php

namespace App\DTO\Domain;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateDomainDTO extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(255)]
        public string|Optional $hostname = new Optional(),

        #[Sometimes, StringType]
        public string|Optional $method = new Optional(),

        #[Sometimes, Min(10), Max(86400)]
        public int|Optional $interval = new Optional(),

        #[Sometimes, Min(1), Max(120)]
        public int|Optional $timeout = new Optional(),

        #[Sometimes, Nullable, StringType]
        public string|null|Optional $body = new Optional(),

        #[Sometimes]
        public bool|Optional $is_active = new Optional(),
    ) {}

    public static function rules(): array
    {
        return [
            'method' => ['sometimes', 'in:GET,HEAD'],
        ];
    }
}
