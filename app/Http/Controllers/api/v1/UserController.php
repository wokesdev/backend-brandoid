<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Getting all users.
        $users = User::all();

        // Returning success API response.
        return $this->success($users, 'All users was retrieved successfully.');
    }

    public function update(Request $request, User $user)
    {
        // Unbanning selected user.
        $unbanUser = User::where('id', $user->id)->update([
            'is_banned' => null
        ]);

        // Returning success API response.
        return $this->success(null, 'User was unbanned successfully.');
    }

    public function destroy(User $user)
    {
        // Banning selected user.
        $banUser = User::where('id', $user->id)->update([
            'is_banned' => true
        ]);

        // Returning success API response.
        return $this->success(null, 'User was banned successfully.');
    }
}
