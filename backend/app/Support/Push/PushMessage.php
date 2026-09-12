<?php

namespace App\Support\Push;

readonly class PushMessage
{
    public function __construct(
        public string $token,
        public string $title,
        public string $body = '',
        public array $data = [],
    ) {}
}
