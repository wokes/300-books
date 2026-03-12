<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Author> */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'last_book_title' => null,
        ];
    }
}
