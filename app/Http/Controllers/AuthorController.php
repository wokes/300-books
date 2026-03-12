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

    #[Endpoint(title: 'List all authors')]
    public function index(): AnonymousResourceCollection
    {
        return AuthorResource::collection($this->authorService->getAll());
    }

    #[Endpoint(title: 'Get author details')]
    public function show(Author $author): AuthorResource
    {
        return new AuthorResource($this->authorService->getById($author));
    }
}
