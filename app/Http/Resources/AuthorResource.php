<?php

namespace App\Http\Resources;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Author */
class AuthorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_book_title' => $this->last_book_title,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'books' => $this->whenLoaded('books', fn () => $this->books->map(fn ($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'isbn' => $book->isbn,
                'created_at' => $book->created_at,
                'updated_at' => $book->updated_at,
            ])),
        ];
    }
}
