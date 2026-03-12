<?php

use App\Models\Author;
use App\Models\Book;

describe('GET /api/authors', function () {
    it('returns a list of all authors with their books', function () {
        $authors = Author::factory()->count(3)->create();
        $book = Book::factory()->create();
        $authors->each(fn (Author $author) => $author->books()->attach($book));

        $response = $this->getJson('/api/authors');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'last_book_title', 'books'],
                ],
            ]);
    });

    it('returns an empty list when no authors exist', function () {
        $response = $this->getJson('/api/authors');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('GET /api/authors/{author}', function () {
    it('returns author details with books', function () {
        $author = Author::factory()->create();
        $books = Book::factory()->count(2)->create();
        $author->books()->attach($books->pluck('id'));

        $response = $this->getJson("/api/authors/{$author->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $author->id)
            ->assertJsonPath('data.name', $author->name)
            ->assertJsonCount(2, 'data.books');
    });

    it('returns 404 for non-existent author', function () {
        $response = $this->getJson('/api/authors/999');

        $response->assertNotFound();
    });
});
