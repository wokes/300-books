<?php

namespace App\Services;

use App\Models\Author;
use App\Services\Interfaces\AuthorServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class AuthorService implements AuthorServiceInterface
{
    /** @return Collection<int, Author> */
    public function getAll(): Collection
    {
        return Author::with('books')->get();
    }

    public function getById(Author $author): Author
    {
        return $author->load('books');
    }
}
