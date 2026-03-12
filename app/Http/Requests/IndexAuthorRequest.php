<?php

namespace App\Http\Requests;

class IndexAuthorRequest extends IndexRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => ['sometimes', 'string', 'max:255'],
        ]);
    }

    public function search(): ?string
    {
        return $this->validated('search');
    }
}
