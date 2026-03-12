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
        return DB::transaction(function () use ($data) {
            $book = Book::create([
                'title' => $data->title,
                'isbn' => $data->isbn,
            ]);

            $authorIds = collect($data->authors)->unique()->map(
                fn (string $name) => Author::firstOrCreate(['name' => $name])->id,
            )->values()->all();

            $book->authors()->attach($authorIds);

            UpdateAuthorsLastBookTitle::dispatch($authorIds);

            return $book->load('authors');
        });
    }

    public function update(Book $book, UpdateBookData $data): Book
    {
        return DB::transaction(function () use ($book, $data) {
            $book->update(array_filter([
                'title' => $data->title,
                'isbn' => $data->isbn,
            ], fn ($value) => $value !== null));

            if ($data->authors !== null) {
                $previousAuthorIds = $book->authors()->pluck('authors.id')->all();

                $newAuthorIds = collect($data->authors)->unique()->map(
                    fn (string $name) => Author::firstOrCreate(['name' => $name])->id,
                )->values()->all();

                $book->authors()->sync($newAuthorIds);

                $affectedAuthorIds = array_values(array_unique([...$previousAuthorIds, ...$newAuthorIds]));

                UpdateAuthorsLastBookTitle::dispatch($affectedAuthorIds);
            }

            return $book->load('authors');
        });
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
