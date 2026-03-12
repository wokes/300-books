<?php

namespace App\Http\Controllers;

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

    #[Endpoint(title: 'List all books')]
    public function index(): AnonymousResourceCollection
    {
        return BookResource::collection($this->bookService->getAll());
    }

    #[Endpoint(title: 'Create a book')]
    #[Response(status: 201)]
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->create($request->toDto());

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    #[Endpoint(title: 'Get book details')]
    public function show(Book $book): BookResource
    {
        return new BookResource($this->bookService->getById($book));
    }

    #[Endpoint(title: 'Update a book')]
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
    }

    #[Endpoint(title: 'Delete a book')]
    #[Response(status: 204)]
    public function destroy(Book $book): JsonResponse
    {
        $this->bookService->delete($book);

        return response()->json(null, 204);
    }
}
