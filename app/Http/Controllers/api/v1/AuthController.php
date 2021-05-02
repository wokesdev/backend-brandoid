<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Using ApiResponser's Trait
    use ApiResponser;

    public function register(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed'
        ]);

        // Creating new user.
        $user = User::create([
            'name' => $attr['name'],
            'password' => Hash::make($attr['password']),
            'email' => $attr['email']
        ]);

        // Returning success API response and creating API Auth Token for the bew registered user.
        return $this->success([
            'token' => $user->createToken('API Auth Token')->plainTextToken
        ], 'User was created successfully.');
    }

    public function login(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'email' => 'required|string|email|',
            'password' => 'required|string|min:6'
        ]);

        // Trying to login using given credentials
        if (!Auth::attempt($attr)) {
            return $this->error('Credentials not match.', 401);
        }

        // Checking whether the user is banned or no.
        if (auth()->user()->is_banned == 1) {
            return $this->error('Your account was banned, please contact administrator for details.', 401);
        }

        // Returning success API response and creating API Auth Token for the authenticated user.
        return $this->success([
            'token' => auth()->user()->createToken('API Auth Token')->plainTextToken
        ], 'Login was successful.');
    }

    public function logout()
    {
        // Deleting all API Auth Token for the authenticated user.
        $deleteToken = auth()->user()->tokens()->delete();

        // Returning success API response.
        return $this->success(null, 'Logout was successful, tokens already revoked.');
    }

    public function profile()
    {
        $user = auth()->user();

        return $this->success($user, 'User profile was retrieved successfully.');
    }
}
