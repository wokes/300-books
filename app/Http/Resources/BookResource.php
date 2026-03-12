<?php

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Book */
class BookResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'isbn' => $this->isbn,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'authors' => $this->whenLoaded('authors', fn () => $this->authors->map(fn ($author) => [
                'id' => $author->id,
                'name' => $author->name,
                'last_book_title' => $author->last_book_title,
            ])),
        ];
    }
}
