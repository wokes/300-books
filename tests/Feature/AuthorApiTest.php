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

describe('GET /api/authors?search=', function () {
    it('filters authors by book title', function () {
        $matchingAuthor = Author::factory()->create();
        $nonMatchingAuthor = Author::factory()->create();

        $matchingBook = Book::factory()->create(['title' => 'Laravel Testing Guide']);
        $otherBook = Book::factory()->create(['title' => 'Cooking Recipes']);

        $matchingAuthor->books()->attach($matchingBook);
        $nonMatchingAuthor->books()->attach($otherBook);

        $response = $this->getJson('/api/authors?search=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingAuthor->id);
    });

    it('returns empty results when no books match search', function () {
        $author = Author::factory()->create();
        $book = Book::factory()->create(['title' => 'PHP Basics']);
        $author->books()->attach($book);

        $response = $this->getJson('/api/authors?search=NonExistentTitle');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('performs case-insensitive search', function () {
        $author = Author::factory()->create();
        $book = Book::factory()->create(['title' => 'Laravel Testing Guide']);
        $author->books()->attach($book);

        $response = $this->getJson('/api/authors?search=laravel testing');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('returns all authors when search is not provided', function () {
        Author::factory()->count(3)->create();

        $response = $this->getJson('/api/authors');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });
});
