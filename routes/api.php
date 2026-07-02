<?php

use App\Http\Controllers\Api\CurrentUserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', CurrentUserController::class)->middleware('auth:sanctum');
