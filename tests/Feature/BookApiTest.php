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

        it('validates required fields', function () {
            $response = $this->postJson('/api/books', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['title', 'isbn', 'authors']);
        });

        it('validates title must be a string', function () {
            $response = $this->postJson('/api/books', [
                'title' => 12345,
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['title']);
        });

        it('validates title max length', function () {
            $response = $this->postJson('/api/books', [
                'title' => str_repeat('a', 256),
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['title']);
        });

        it('accepts title at max length', function () {
            $response = $this->postJson('/api/books', [
                'title' => str_repeat('a', 255),
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author'],
            ]);

            $response->assertCreated();
        });

        it('validates isbn must be a string', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => 9783161484100,
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('validates isbn max length', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => str_repeat('1', 18),
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('validates isbn format for isbn-13 with bad checksum', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-9',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('validates isbn format for isbn-10 with bad checksum', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '0-306-40615-X',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('rejects isbn-13 not starting with 978 or 979', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '9771234567897',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('rejects isbn with only hyphens', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '---',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('rejects isbn with letters in body', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-ABC-DEF-0',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('accepts a valid isbn-10 without hyphens', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '0306406152',
                'authors' => ['Author'],
            ]);

            $response->assertCreated();
        });

        it('accepts a valid isbn-10 with hyphens', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '0-306-40615-2',
                'authors' => ['Author'],
            ]);

            $response->assertCreated();
        });

        it('accepts a valid isbn-10 ending with X', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '080442957X',
                'authors' => ['Author'],
            ]);

            $response->assertCreated();
        });

        it('accepts a valid isbn-13 without hyphens', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '9783161484100',
                'authors' => ['Author'],
            ]);

            $response->assertCreated();
        });

        it('accepts a valid isbn-13 with hyphens', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author'],
            ]);

            $response->assertCreated();
        });

        it('accepts a valid isbn-13 starting with 979', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '9791034344109',
                'authors' => ['Author'],
            ]);

            $response->assertCreated();
        });

        it('validates isbn uniqueness', function () {
            Book::factory()->create(['isbn' => '978-3-16-148410-0']);

            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => ['Author'],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('validates authors must be an array', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => 'not an array',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['authors']);
        });

        it('validates authors must be a non-empty array', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => [],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['authors']);
        });

        it('validates author names are strings', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => [123],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['authors.0']);
        });

        it('validates author name max length', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => [str_repeat('a', 256)],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['authors.0']);
        });

        it('accepts author name at max length', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => [str_repeat('a', 255)],
            ]);

            $response->assertCreated();
        });

        it('validates null values are rejected', function () {
            $response = $this->postJson('/api/books', [
                'title' => null,
                'isbn' => null,
                'authors' => null,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['title', 'isbn', 'authors']);
        });

        it('validates author entries cannot be null', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => [null],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['authors.0']);
        });

        it('validates author entries cannot be empty strings', function () {
            $response = $this->postJson('/api/books', [
                'title' => 'Test',
                'isbn' => '978-3-16-148410-0',
                'authors' => [''],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['authors.0']);
        });

        it('does not create book when validation fails', function () {
            $this->postJson('/api/books', [
                'title' => '',
                'isbn' => 'invalid',
                'authors' => [],
            ]);

            expect(Book::count())->toBe(0);
            expect(Author::count())->toBe(0);
        });

        it('does not dispatch job when validation fails', function () {
            $this->postJson('/api/books', []);

            Queue::assertNothingPushed();
        });
    });

    describe('PUT /api/books/{book}', function () {
        it('updates book title', function () {
            $book = Book::factory()->create();
            $author = Author::factory()->create();
            $book->authors()->attach($author);

            $response = $this->putJson("/api/books/{$book->id}", [
                'title' => 'Updated Title',
            ]);

            $response->assertOk()
                ->assertJsonPath('data.title', 'Updated Title');

            $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Updated Title']);
        });

        it('updates book isbn', function () {
            $book = Book::factory()->create();

            $response = $this->putJson("/api/books/{$book->id}", [
                'isbn' => '978-3-16-148410-0',
            ]);

            $response->assertOk()
                ->assertJsonPath('data.isbn', '978-3-16-148410-0');
        });

        it('allows keeping the same isbn on update', function () {
            $book = Book::factory()->create(['isbn' => '978-3-16-148410-0']);

            $response = $this->putJson("/api/books/{$book->id}", [
                'isbn' => '978-3-16-148410-0',
            ]);

            $response->assertOk();
        });

        it('rejects duplicate isbn from another book', function () {
            Book::factory()->create(['isbn' => '978-3-16-148410-0']);
            $book = Book::factory()->create();

            $response = $this->putJson("/api/books/{$book->id}", [
                'isbn' => '978-3-16-148410-0',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['isbn']);
        });

        it('updates author relationships', function () {
            $book = Book::factory()->create();
            $oldAuthor = Author::factory()->create(['name' => 'Old Author']);
            $book->authors()->attach($oldAuthor);

            $response = $this->putJson("/api/books/{$book->id}", [
                'authors' => ['New Author One', 'New Author Two'],
            ]);

            $response->assertOk()
                ->assertJsonCount(2, 'data.authors');

            $book->refresh();
            expect($book->authors->pluck('name')->all())->toEqualCanonicalizing(['New Author One', 'New Author Two']);

            Queue::assertPushed(UpdateAuthorsLastBookTitle::class, function ($job) use ($oldAuthor) {
                return in_array($oldAuthor->id, $job->authorIds);
            });
        });

        it('returns 404 for non-existent book', function () {
            $response = $this->putJson('/api/books/999', [
                'title' => 'Updated',
            ]);

            $response->assertNotFound();
        });
    });

    describe('DELETE /api/books/{book}', function () {
        it('deletes a book and dispatches author update job', function () {
            $book = Book::factory()->create();
            $author = Author::factory()->create();
            $book->authors()->attach($author);

            $response = $this->deleteJson("/api/books/{$book->id}");

            $response->assertNoContent();

            $this->assertDatabaseMissing('books', ['id' => $book->id]);
            $this->assertDatabaseMissing('author_book', ['book_id' => $book->id]);

            Queue::assertPushed(UpdateAuthorsLastBookTitle::class, function ($job) use ($author) {
                return in_array($author->id, $job->authorIds);
            });
        });

        it('returns empty body with 204 status', function () {
            $book = Book::factory()->create();

            $response = $this->deleteJson("/api/books/{$book->id}");

            $response->assertNoContent();
            expect($response->getContent())->toBeEmpty();
        });

        it('dispatches job with all author ids when book has multiple authors', function () {
            $book = Book::factory()->create();
            $authors = Author::factory()->count(3)->create();
            $book->authors()->attach($authors->pluck('id'));

            $this->deleteJson("/api/books/{$book->id}");

            Queue::assertPushed(UpdateAuthorsLastBookTitle::class, function ($job) use ($authors) {
                return count($job->authorIds) === 3
                    && collect($authors->pluck('id'))->every(fn ($id) => in_array($id, $job->authorIds));
            });
        });

        it('does not dispatch job when book has no authors', function () {
            $book = Book::factory()->create();

            $this->deleteJson("/api/books/{$book->id}");

            Queue::assertNothingPushed();
        });

        it('preserves author records after book deletion', function () {
            $book = Book::factory()->create();
            $author = Author::factory()->create();
            $book->authors()->attach($author);

            $this->deleteJson("/api/books/{$book->id}");

            $this->assertDatabaseHas('authors', ['id' => $author->id]);
        });

        it('removes pivot records on deletion', function () {
            $book = Book::factory()->create();
            $authors = Author::factory()->count(2)->create();
            $book->authors()->attach($authors->pluck('id'));

            $this->assertDatabaseCount('author_book', 2);

            $this->deleteJson("/api/books/{$book->id}");

            $this->assertDatabaseCount('author_book', 0);
        });

        it('does not affect other books when deleting', function () {
            $author = Author::factory()->create();
            $bookToKeep = Book::factory()->create();
            $bookToDelete = Book::factory()->create();
            $bookToKeep->authors()->attach($author);
            $bookToDelete->authors()->attach($author);

            $this->deleteJson("/api/books/{$bookToDelete->id}");

            $this->assertDatabaseHas('books', ['id' => $bookToKeep->id]);
            $this->assertDatabaseHas('author_book', [
                'book_id' => $bookToKeep->id,
                'author_id' => $author->id,
            ]);
        });

        it('returns 404 for non-existent book', function () {
            $response = $this->deleteJson('/api/books/999');

            $response->assertNotFound();
        });

        it('returns 404 when deleting an already deleted book', function () {
            $book = Book::factory()->create();
            $bookId = $book->id;

            $this->deleteJson("/api/books/{$bookId}")->assertNoContent();
            $this->deleteJson("/api/books/{$bookId}")->assertNotFound();
        });
    });
});
