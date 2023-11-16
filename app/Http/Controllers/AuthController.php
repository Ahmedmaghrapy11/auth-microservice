<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required','string','confirmed','min:8','max:32', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[*!$#%]).*$/',],
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
        ]);
        $token = JWTAuth::fromUser($user);
        return response()->json(['message' => 'A new user has registered successfully!', 'token' => $token]);
    }

    public function login(Request $request) {
        $data = $request->only("email","password");
        if (!$token = JWTAuth::attempt($data)) {
            return response()->json(["error"=> "Invalid email or password, Please try again !"], 401);
        }
        return response()->json(["message"=> "Logged in successfully!", 'token' => $token]);
    }

    public function refreshToken()
    {
        $token = JWTAuth::refresh();
        return response()->json(['token' => $token]);
    }

    public function logout()
    {
        JWTAuth::invalidate();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
