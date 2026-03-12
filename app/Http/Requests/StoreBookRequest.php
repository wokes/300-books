<?php

namespace App\Http\Requests;

use App\DTOs\StoreBookData;
use App\Rules\Isbn;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:17', new Isbn, 'unique:books,isbn'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): StoreBookData
    {
        return new StoreBookData(
            title: $this->validated('title'),
            isbn: $this->validated('isbn'),
            authors: $this->validated('authors'),
        );
    }
}
