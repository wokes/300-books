<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $authorIds = Author::pluck('id')->all();

        Book::factory()
            ->count(500)
            ->create()
            ->each(function (Book $book) use ($authorIds) {
                $randomAuthorIds = collect($authorIds)
                    ->random(rand(1, 3))
                    ->all();

                $book->authors()->attach($randomAuthorIds);
            });

        Author::all()->each(function (Author $author) {
            $lastBookTitle = $author->books()
                ->orderByPivot('created_at', 'desc')
                ->value('title');

            $author->update(['last_book_title' => $lastBookTitle]);
        });
    }
}
