<?php

namespace App\DTOs;

readonly class UpdateBookData
{
    /**
     * @param  ?string[]  $authors
     */
    public function __construct(
        public ?string $title = null,
        public ?string $isbn = null,
        public ?array $authors = null,
    ) {}
}
