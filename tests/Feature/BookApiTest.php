<?php

use App\Jobs\UpdateAuthorsLastBookTitle;
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

    describe('POST /api/books', function () {
        it('creates a new book with new authors', function () {
            $payload = [
                'title' => 'Test Book',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author One', 'Author Two'],
            ];

            $response = $this->postJson('/api/books', $payload);

            $response->assertCreated()
                ->assertJsonPath('data.title', 'Test Book')
                ->assertJsonPath('data.isbn', '978-3-16-148410-0')
                ->assertJsonCount(2, 'data.authors');

            $this->assertDatabaseHas('books', ['title' => 'Test Book', 'isbn' => '978-3-16-148410-0']);
            $this->assertDatabaseHas('authors', ['name' => 'Author One']);
            $this->assertDatabaseHas('authors', ['name' => 'Author Two']);

            Queue::assertPushed(UpdateAuthorsLastBookTitle::class);
        });

        it('returns the correct response structure', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Structure Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author One'],
            ]);

            $response->assertCreated()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'isbn',
                        'created_at',
                        'updated_at',
                        'authors' => [
                            '*' => ['id', 'name', 'last_book_title'],
                        ],
                    ],
                ]);
        });

        it('creates a book with a single author', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Solo Author Book',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Solo Author'],
            ]);

            $response->assertCreated()
                ->assertJsonCount(1, 'data.authors')
                ->assertJsonPath('data.authors.0.name', 'Solo Author');
        });

        it('creates a book with three authors', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Trio Book',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author A', 'Author B', 'Author C'],
            ]);

            $response->assertCreated()
                ->assertJsonCount(3, 'data.authors');
        });

        it('reuses existing authors by name', function () {
            $existingAuthor = Author::factory()->create(['name' => 'Existing Author']);

            $payload = [
                'title' => 'Another Book',
                'isbn' => '978-0-13-468599-1',
                'authors' => ['Existing Author', 'New Author'],
            ];

            $response = $this->postJson('/api/books', $payload);

            $response->assertCreated();

            expect(Author::where('name', 'Existing Author')->count())->toBe(1);
            expect(Author::where('name', 'New Author')->count())->toBe(1);

            $book = Book::where('isbn', '978-0-13-468599-1')->first();
            expect($book->authors)->toHaveCount(2);
            expect($book->authors->pluck('id'))->toContain($existingAuthor->id);
        });

        it('dispatches job with correct author ids including existing authors', function () {
            $existingAuthor = Author::factory()->create(['name' => 'Existing']);

            $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Existing', 'Brand New'],
            ]);

            $newAuthor = Author::where('name', 'Brand New')->first();

            Queue::assertPushed(UpdateAuthorsLastBookTitle::class, function ($job) use ($existingAuthor, $newAuthor) {
                return count($job->authorIds) === 2
                    && in_array($existingAuthor->id, $job->authorIds)
                    && in_array($newAuthor->id, $job->authorIds);
            });
        });

        it('handles duplicate author names in the array', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Duped Authors',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Same Author', 'Same Author'],
            ]);

            $response->assertCreated();

            expect(Author::where('name', 'Same Author')->count())->toBe(1);
        });
    });
});
