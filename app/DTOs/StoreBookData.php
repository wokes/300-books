<?php

namespace App\DTOs;

readonly class StoreBookData
{
    /**
     * @param  string[]  $authors
     */
    public function __construct(
        public string $title,
        public string $isbn,
        public array $authors,
    ) {}
}
