<?php

namespace App\Services\Interfaces;

use App\Models\Author;
use Illuminate\Database\Eloquent\Collection;

interface AuthorServiceInterface
{
    /** @return Collection<int, Author> */
    public function getAll(): Collection;

    public function getById(Author $author): Author;
}
