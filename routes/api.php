<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Registration endpoint
Route::post('/register', [AuthController::class, 'register' 

]);
