<?php

namespace App\Services\Interfaces;

use App\DTOs\StoreBookData;
use App\DTOs\UpdateBookData;
use App\Models\Book;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookServiceInterface
{
    /** @return LengthAwarePaginator<int, Book> */
    public function getAll(int $perPage = 15): LengthAwarePaginator;

    public function getById(Book $book): Book;

    public function create(StoreBookData $data): Book;

    public function update(Book $book, UpdateBookData $data): Book;

    public function delete(Book $book): void;
}
