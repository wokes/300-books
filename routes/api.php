<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

// Required auth in accordance with the task description - it only mentioned this endpoint.
Route::apiResource('books', BookController::class)->only(['store'])->middleware('auth:sanctum');

// Public endpoints
Route::apiResource('books', BookController::class)->except(['store']);
Route::apiResource('authors', AuthorController::class)->only(['index', 'show']);
