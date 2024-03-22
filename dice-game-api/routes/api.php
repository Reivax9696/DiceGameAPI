<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Create a player
Route::post('/players', [UserController::class, 'create']);
// Player rolls the dice
Route::post('/players/{id}/games', [GameController::class, 'roll']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
