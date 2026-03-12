<?php

namespace App\Services;

use App\DTOs\StoreBookData;
use App\DTOs\UpdateBookData;
use App\Jobs\UpdateAuthorsLastBookTitle;
use App\Models\Author;
use App\Models\Book;
use App\Services\Interfaces\BookServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BookService implements BookServiceInterface
{
    /** @return Collection<int, Book> */
    public function getAll(): Collection
    {
    }

    public function getById(Book $book): Book
    {
    }

    public function create(StoreBookData $data): Book
    {
    }

    public function update(Book $book, UpdateBookData $data): Book
    {
    }

    public function delete(Book $book): void
    {
    }
}
