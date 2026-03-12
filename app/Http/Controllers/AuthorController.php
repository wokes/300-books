<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthorResource;
use App\Models\Author;
use App\Services\Interfaces\AuthorServiceInterface;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Authors')]
class AuthorController extends Controller
{
    public function __construct(
        private readonly AuthorServiceInterface $authorService,
    ) {}

    #[Endpoint(operationId: 'authors.index', title: 'List all authors', description: 'Retrieve a list of all authors.')]
    public function index(): AnonymousResourceCollection
    {
        return AuthorResource::collection($this->authorService->getAll());
    }

    #[Endpoint(operationId: 'authors.show', title: 'Get author details', description: 'Retrieve the details of a specific author by their ID.')]
    public function show(Author $author): AuthorResource
    {
        return new AuthorResource($this->authorService->getById($author));
    }
}
