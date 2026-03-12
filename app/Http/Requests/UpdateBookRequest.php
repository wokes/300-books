<?php

namespace App\Http\Requests;

use App\DTOs\UpdateBookData;
use App\Rules\Isbn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'isbn' => ['sometimes', 'string', 'max:17', new Isbn, Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'authors' => ['sometimes', 'array', 'min:1'],
            'authors.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): UpdateBookData
    {
        $validated = $this->validated();

        return new UpdateBookData(
            title: $validated['title'] ?? null,
            isbn: $validated['isbn'] ?? null,
            authors: $validated['authors'] ?? null,
        );
    }
}
