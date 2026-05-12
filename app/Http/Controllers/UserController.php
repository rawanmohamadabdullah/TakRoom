<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

   //Register User
    public function register(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|string',
                'email' => 'required|email|string|unique:users,email|max:255',
                'password' => 'required|string|min:8',
                'room_number' => 'required|string',
                'bulding' => 'required|string'
            ]
        );
        $user = User::create(
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'room_number' => $request->room_number,
                'bulding' => $request->bulding

            ]
        );
        $token = $user->createToken('auth_Token')->plainTextToken;
        return response()->json([
            'message' => 'User Registered Successfuly',
            'User' => $user,
            'token' => $token
        ], 201);
    }

    //Login User
    public function login(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email',
                'password' => 'required'
            ]
        );
        if (!Auth::attempt($request->only('email','password')))
            return response()->json(['message'=>'Error In Your Email OR Password'], 401);
            $user=User::where('email',$request->email)->firstOrFail();
            $token=$user->createToken('auth_Token')->plainTextToken;
            return response()->json([
                 'message'=>'Login Successful',
                 'token'=>$token,
                 'user'=>$user
            ], );
    }
     //Logout User
     }

