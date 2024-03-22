<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Delete the player's rolls
Route::delete('/players/{id}/games', [GameController::class, 'destroy']);

// Get the list of games for a player
Route::get('/players/{id}/games', [GameController::class, 'index']);

// Modify the name of the player
Route::put('/players/{id}', [UserController::class, 'update']);

// Get the list of all players
Route::get('/players', [UserController::class, 'index']);

// Get the average success rate of all players
Route::get('/players/ranking', [UserController::class, 'ranking']);

// Get the player with the worst success rate
Route::get('/players/ranking/loser', [UserController::class, 'loser']);

// Get the player with the best success rate
Route::get('/players/ranking/winner', [UserController::class, 'winner']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
