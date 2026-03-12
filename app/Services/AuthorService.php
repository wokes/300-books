<?php

namespace App\Services;

use App\Models\Author;
use App\Services\Interfaces\AuthorServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AuthorService implements AuthorServiceInterface
{
    /** @return LengthAwarePaginator<int, Author> */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Author::with('books')->paginate($perPage);
    }

    public function getById(Author $author): Author
    {
        return $author->load('books');
    }
}
