<?php

namespace App\Jobs;

use App\Models\Author;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAuthorsLastBookTitle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  int[]  $authorIds
     */
    public function __construct(
        public readonly array $authorIds,
    ) {}

    public function handle(): void
    {
        // Each is suboptimal, but it's unlikely we'll get a book with a million authors.
        Author::whereIn('id', $this->authorIds)
            ->each(function (Author $author) {
                $lastBookTitle = $author->books()
                    ->orderByPivot('created_at', 'desc')
                    ->value('title');

                $author->update(['last_book_title' => $lastBookTitle]);
            });
    }
}
