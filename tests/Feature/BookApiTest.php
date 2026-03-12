<?php

use App\Models\Author;
use App\Models\Book;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

describe('GET /api/books', function () {
    it('returns a list of all books with their authors', function () {
        $author = Author::factory()->create();
        $books = Book::factory()->count(3)->create();
        $books->each(fn (Book $book) => $book->authors()->attach($author));

        $response = $this->getJson('/api/books');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'isbn', 'created_at', 'updated_at', 'authors'],
                ],
            ]);
    });

    it('returns an empty list when no books exist', function () {
        $response = $this->getJson('/api/books');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    describe('GET /api/books/{book}', function () {
        it('returns book details with authors', function () {
            $book = Book::factory()->create();
            $authors = Author::factory()->count(2)->create();
            $book->authors()->attach($authors->pluck('id'));

            $response = $this->getJson("/api/books/{$book->id}");

            $response->assertOk()
                ->assertJsonPath('data.id', $book->id)
                ->assertJsonPath('data.title', $book->title)
                ->assertJsonPath('data.isbn', $book->isbn)
                ->assertJsonCount(2, 'data.authors');
        });

        it('returns 404 for non-existent book', function () {
            $response = $this->getJson('/api/books/999');

            $response->assertNotFound();
        });
    });
});
