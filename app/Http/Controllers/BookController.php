<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\Interfaces\BookServiceInterface;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Books')]
class BookController extends Controller
{
    public function __construct(
        private readonly BookServiceInterface $bookService,
    ) {}

    #[Endpoint(operationId: 'books.index', title: 'List all books', description: 'Retrieve a paginated list of all books.')]
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return BookResource::collection($this->bookService->getAll($request->perPage()));
    }

    #[Endpoint(operationId: 'books.store', title: 'Create a book', description: 'Store a newly created book and associate it with authors.')]
    #[Response(status: 201)]
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->create($request->toDto());

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    #[Endpoint(operationId: 'books.show', title: 'Get book details', description: 'Retrieve the details of a specific book by its ID.')]
    public function show(Book $book): BookResource
    {
        return new BookResource($this->bookService->getById($book));
    }

    #[Endpoint(operationId: 'books.update', title: 'Update a book', description: 'Update the details of an existing book.')]
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        return new BookResource($this->bookService->update($book, $request->toDto()));
    }

    #[Endpoint(operationId: 'books.destroy', title: 'Delete a book', description: 'Remove a book from the collection.')]
    #[Response(status: 204)]
    public function destroy(Book $book): JsonResponse
    {
        $this->bookService->delete($book);

        return response()->json(null, 204);
    }
}
