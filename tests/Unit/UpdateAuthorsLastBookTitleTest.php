<?php

use App\Jobs\UpdateAuthorsLastBookTitle;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UpdateAuthorsLastBookTitle job', function () {
    it('updates author last_book_title to most recent book title', function () {
        $author = Author::factory()->create(['last_book_title' => null]);

        $oldBook = Book::factory()->create(['title' => 'Old Book']);
        $author->books()->attach($oldBook);

        // Ensure second attach has a later timestamp
        $this->travel(1)->seconds();

        $newBook = Book::factory()->create(['title' => 'New Book']);
        $author->books()->attach($newBook);

        $job = new UpdateAuthorsLastBookTitle([$author->id]);
        $job->handle();

        $author->refresh();
        expect($author->last_book_title)->toBe('New Book');
    });

    it('sets last_book_title to null when author has no books', function () {
        $author = Author::factory()->create(['last_book_title' => 'Some Book']);

        $job = new UpdateAuthorsLastBookTitle([$author->id]);
        $job->handle();

        $author->refresh();
        expect($author->last_book_title)->toBeNull();
    });

    it('updates multiple authors at once', function () {
        $author1 = Author::factory()->create(['last_book_title' => null]);
        $author2 = Author::factory()->create(['last_book_title' => null]);

        $book = Book::factory()->create(['title' => 'Shared Book']);
        $author1->books()->attach($book);
        $author2->books()->attach($book);

        $job = new UpdateAuthorsLastBookTitle([$author1->id, $author2->id]);
        $job->handle();

        $author1->refresh();
        $author2->refresh();
        expect($author1->last_book_title)->toBe('Shared Book');
        expect($author2->last_book_title)->toBe('Shared Book');
    });

    it('handles empty author ids array gracefully', function () {
        $job = new UpdateAuthorsLastBookTitle([]);
        $job->handle();

        // No exception means success
        expect(true)->toBeTrue();
    });
});
