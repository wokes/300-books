<?php

namespace App\Services;

use App\Models\Author;
use App\Services\Interfaces\AuthorServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AuthorService implements AuthorServiceInterface
{
    /** @return LengthAwarePaginator<int, Author> */
    public function getAll(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Author::with('books');

        if ($search !== null) {
            $query->whereHas('books', function ($q) use ($search) {
                $q->whereRaw('title ILIKE ?', ['%'.$search.'%']);
            });
        }

        return $query->paginate($perPage);
    }

    public function getById(Author $author): Author
    {
        return $author->load('books');
    }
}
