<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'nickname' => 'required|unique:users,nickname',
            'password' => 'required',
        ]);

        $hashedPassword = Hash::make($request->password);

        $user = User::create([
            'email' => $request->email,
            'nickname' => $request->nickname ?? 'Anonymous',
            'password' => $hashedPassword,
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }
}
