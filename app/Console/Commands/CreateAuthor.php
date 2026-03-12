<?php

namespace App\Console\Commands;

use App\Models\Author;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAuthor extends Command
{
    protected $signature = 'author:create';

    protected $description = 'Create a new author record';

    public function handle(): int
    {
        $name = $this->promptForName();

        $author = Author::create(['name' => $name]);

        $this->info("Author '{$author->name}' created successfully (ID: {$author->id}).");

        return self::SUCCESS;
    }

    private function promptForName(): string
    {
        while (true) {
            $input = trim((string) $this->ask('What is the author name?'));

            $validator = Validator::make(
                ['name' => $input],
                ['name' => ['required', 'string', 'max:255']],
            );

            if ($validator->passes()) {
                return $input;
            }

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
        }
    }
}
