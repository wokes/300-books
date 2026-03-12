<?php

use App\DTOs\StoreBookData;
use App\Jobs\UpdateAuthorsLastBookTitle;
use App\Models\Author;
use App\Services\BookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

describe('BookService::create', function () {
    it('creates a book with correct attributes', function () {
        $service = app(BookService::class);

        $dto = new StoreBookData(
            title: 'Test Book',
            isbn: '978-3-16-148410-0',
            authors: ['Author One'],
        );

        $book = $service->create($dto);

        expect($book->title)->toBe('Test Book');
        expect($book->isbn)->toBe('978-3-16-148410-0');
        $this->assertDatabaseHas('books', ['title' => 'Test Book', 'isbn' => '978-3-16-148410-0']);
    });

    it('creates new authors and attaches them', function () {
        $service = app(BookService::class);

        $dto = new StoreBookData(
            title: 'Multi-Author Book',
            isbn: '978-3-16-148410-0',
            authors: ['Alice', 'Bob', 'Charlie'],
        );

        $book = $service->create($dto);

        expect($book->authors)->toHaveCount(3);
        expect($book->authors->pluck('name')->all())->toEqualCanonicalizing(['Alice', 'Bob', 'Charlie']);
    });

    it('reuses existing authors by name', function () {
        $existingAuthor = Author::factory()->create(['name' => 'Existing Author']);

        $service = app(BookService::class);

        $dto = new StoreBookData(
            title: 'Another Book',
            isbn: '978-3-16-148410-0',
            authors: ['Existing Author', 'New Author'],
        );

        $book = $service->create($dto);

        expect(Author::where('name', 'Existing Author')->count())->toBe(1);
        expect($book->authors->pluck('id'))->toContain($existingAuthor->id);
    });

    it('dispatches UpdateAuthorsLastBookTitle job with correct author ids', function () {
        $service = app(BookService::class);

        $dto = new StoreBookData(
            title: 'Test Book',
            isbn: '978-3-16-148410-0',
            authors: ['Author One', 'Author Two'],
        );

        $book = $service->create($dto);

        $authorIds = $book->authors->pluck('id')->all();

        Queue::assertPushed(UpdateAuthorsLastBookTitle::class, function ($job) use ($authorIds) {
            return $job->authorIds === $authorIds;
        });
    });

    it('returns book with authors loaded', function () {
        $service = app(BookService::class);

        $dto = new StoreBookData(
            title: 'Loaded Book',
            isbn: '978-3-16-148410-0',
            authors: ['Some Author'],
        );

        $book = $service->create($dto);

        expect($book->relationLoaded('authors'))->toBeTrue();
        expect($book->authors->first()->name)->toBe('Some Author');
    });
});
