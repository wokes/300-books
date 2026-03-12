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
        return Book::with('authors')->get();
    }

    public function getById(Book $book): Book
    {
        return $book->load('authors');
    }

    public function create(StoreBookData $data): Book
    {
    }

    public function update(Book $book, UpdateBookData $data): Book
    {
    }

    public function delete(Book $book): void
    {
        DB::transaction(function () use ($book) {
            $authorIds = $book->authors()->pluck('authors.id')->all();

            $book->delete();

            if (! empty($authorIds)) {
                UpdateAuthorsLastBookTitle::dispatch($authorIds);
            }
        });
    }
}
