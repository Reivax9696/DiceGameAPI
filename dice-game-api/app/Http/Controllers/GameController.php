<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function roll(Request $request, $id)
    {

        $user = Auth::user();

        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        $total = $dice1 + $dice2;
        $win = $total === 7 ? true : false;

        $game = Game::create([
            'user_id' => $user->id,
            'dice1' => $dice1,
            'dice2' => $dice2,
            'is_won' => $win
        ]);

        return response()->json($game, 201);
    }

    public function destroy($id)
{
    $user = User::findOrFail(Auth::user()->id);
    $user->games()->delete();

    return response()->json(['message' => 'All games deleted successfully']);
}

    public function index($id)
{
    $user = User::findOrFail(Auth::user()->id);
    $games = $user->games;

    return response()->json(['games' => $games]);
}
}
