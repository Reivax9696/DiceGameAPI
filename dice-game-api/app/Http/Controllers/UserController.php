<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;



class UserController extends Controller
{
    public function create(Request $request)
    {


        $existingEmail = User::where('email', $request->email)->exists();
        if ($existingEmail) {
            return response()->json(['error' => 'Email already exists'], 422);
        }


        $existingNickname = User::where('nickname', $request->nickname)->exists();
        if ($existingNickname) {
            return response()->json(['error' => 'Nickname already exists'], 422);

        }
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'nickname' => 'unique:users,nickname',
            'password' => 'required',
        ]);

        $hashedPassword = Hash::make($request->password);

        $user = User::create([
            'email' => $request->email,
            'nickname' => $request->nickname ?? 'Anonymous',
            'password' => $hashedPassword,
        ]);

        $defaultRole = Role::where('name', 'Player')->first();
        $user->assignRole($defaultRole);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('nickname', 'password');

        if (Auth::attempt($credentials)) {
            $user = User::where('nickname', $request->nickname)->first();
            $token = $user->createToken('MyApp')->accessToken;

            return response()->json(['token' => $token]);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Successfully logged out']);
    }


    public function update(Request $request, $id)
    {

        $user = User::findOrFail(Auth::user()->id);

        $request->validate([
            'nickname' => 'nullable|unique:users,nickname,'.$id,
        ]);

        $user->update([
            'nickname' => $request->nickname ?? $user->nickname,
        ]);

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    public function index()
{
    $users = User::all();

    $users->loadCount(['games', 'games as victories' => function ($query) {
        $query->where('is_won', true);
    }]);

    $users->transform(function ($user) {
        $user->average_victories = $user->games_count > 0 ? $user->victories / $user->games_count : 0;
        return $user;
    });

    return response()->json(['users' => $users]);
}

    public function ranking()
    {
        $users = User::withCount('games')
        ->withCount(['games as victories' => function ($query) {
            $query->where('is_won', true);
        }])
        ->get();

        $users->transform(function ($user) {
            $user->average_victories = $user->games_count > 0 ? $user->victories / $user->games_count : 0;
            return $user;
            });


        $ranking = $users->sortByDesc('average_victories')->values()->all();

        return response()->json(['ranking' => $ranking]);
        }

     public function loser()
     {
         $users = User::withCount('games')
                      ->withCount(['games as victories' => function ($query) {
                          $query->where('is_won', true);
                      }])
                      ->get();

         $users->transform(function ($user) {
             $user->average_victories = $user->games_count > 0 ? $user->victories / $user->games_count : 0;
             return $user;
         });

         $loser = $users->sortBy('average_victories')->first();

         return response()->json(['loser' => $loser]);
     }

     public function winner()
     {
         $users = User::withCount('games')
                      ->withCount(['games as victories' => function ($query) {
                          $query->where('is_won', true);
                      }])
                      ->get();

         $users->transform(function ($user) {
             $user->average_victories = $user->games_count > 0 ? $user->victories / $user->games_count : 0;
             return $user;
         });

         $winner = $users->sortByDesc('average_victories')->first();

         return response()->json(['winner' => $winner]);
     }
}


