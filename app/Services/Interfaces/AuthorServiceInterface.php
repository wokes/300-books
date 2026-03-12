<?php

namespace App\Services\Interfaces;

use App\Models\Author;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AuthorServiceInterface
{
    /** @return LengthAwarePaginator<int, Author> */
    public function getAll(int $perPage = 15): LengthAwarePaginator;

    public function getById(Author $author): Author;
}
