<?php

namespace App\Services\Interfaces;

use App\DTOs\StoreBookData;
use App\DTOs\UpdateBookData;
use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;

interface BookServiceInterface
{
    /** @return Collection<int, Book> */
    public function getAll(): Collection;

    public function getById(Book $book): Book;

    public function create(StoreBookData $data): Book;

    public function update(Book $book, UpdateBookData $data): Book;

    public function delete(Book $book): void;
}
