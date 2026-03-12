<?php

namespace App\Services\Interfaces;

use App\Models\Author;
use Illuminate\Pagination\LengthAwarePaginator;

interface AuthorServiceInterface
{
    /** @return LengthAwarePaginator<int, Author> */
    public function getAll(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function getById(Author $author): Author;
}
