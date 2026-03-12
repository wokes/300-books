<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::apiResource('books', BookController::class);
Route::apiResource('authors', AuthorController::class)->only(['index', 'show']);
