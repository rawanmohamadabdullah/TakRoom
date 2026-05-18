<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|unique:users,email|max:255',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['customer', 'supplier', 'admin'])],
            'room_number' => 'required_if:role,customer,supplier|string|nullable',
            'building' => 'required_if:role,customer,supplier|string|nullable',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'room_number' => $request->role === 'admin' ? null : $request->room_number,
            'building' => $request->role === 'admin' ? null : $request->building,
            'balance' => $request->has('balance') ? $request->balance : 0.00,
        ]);

        return response()->json([
            'message' => 'User Created Successfully By Admin',
            'user' => $user
        ], 201);
    }
    public function changeRole(Request $request, $id)
    {
        $request->validate([
            'role' => ['required', Rule::in(['customer', 'supplier', 'admin'])]
        ]);

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot change your own role.'
            ], 400);
        }

        $user->update([
            'role' => $request->role
        ]);

        return response()->json([
            'message' => "Role updated to {$request->role}",
            'user' => $user
        ], 200);
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own account.'
            ], 400);
        }
        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => 'The account and all associated data have been successfully and permanently deleted.'
        ], 200);
    }
    public function banUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot ban your own account.'
            ], 400);
        }

        $user->update(['is_banned' => true]);

        $user->tokens()->delete();

        return response()->json([
            'message' => "The user {$user->name} has been banned successfully and removed from the system.",
            'user' => $user
        ], 200);
    }

    public function unbanUser($id)
    {
        $user = User::findOrFail($id);

        $user->update(['is_banned' => false]);

        return response()->json([
            'message' => "The ban on user {$user->name} has been lifted successfully, and they can now use the application.",
            'user' => $user
        ], 200);
    }
}
