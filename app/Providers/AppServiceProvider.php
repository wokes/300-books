<?php

namespace App\Providers;

use App\Services\AuthorService;
use App\Services\BookService;
use App\Services\Interfaces\AuthorServiceInterface;
use App\Services\Interfaces\BookServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BookServiceInterface::class, BookService::class);
        $this->app->bind(AuthorServiceInterface::class, AuthorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
