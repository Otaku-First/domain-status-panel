<?php

namespace App\DTO\Domain;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class CreateDomainDTO extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $hostname,

        #[Required, StringType]
        public string $method = 'GET',

        #[Required, Min(10), Max(86400)]
        public int $interval = 60,

        #[Required, Min(1), Max(120)]
        public int $timeout = 30,

        #[Nullable, StringType]
        public ?string $body = null,

        public bool $is_active = true,
    ) {}

    public static function rules(): array
    {
        return [
            'method' => ['required', 'in:GET,HEAD'],
        ];
    }
}
