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
});
