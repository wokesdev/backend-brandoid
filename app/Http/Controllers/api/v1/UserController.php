<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all users.
        $users = User::all();

        // Returning success API response.
        return $this->success($users, 'All users was retrieved successfully.');
    }

    public function makeAdmin(Request $request, User $user)
    {
        // Giving admin privilege selected user.
        $makeAdmin = User::where('id', $user->id)->update([
            'is_admin' => true
        ]);

        // Returning success API response.
        return $this->success(null, 'Admin privilege was successfully given to the user.');
    }

    public function removeAdmin(Request $request, User $user)
    {
        // Removing admin privilege to selected user.
        $removeAdmin = User::where('id', $user->id)->update([
            'is_admin' => null
        ]);

        // Returning success API response.
        return $this->success(null, 'Admin privilege was successfully removed from the user.');
    }

    public function banUser(Request $request, User $user)
    {
        // Banning selected user.
        $banUser = User::where('id', $user->id)->update([
            'is_banned' => true
        ]);

        // Returning success API response.
        return $this->success(null, 'User was banned successfully.');
    }

    public function unbanUser(Request $request, User $user)
    {
        // Unbanning selected user.
        $unbanUser = User::where('id', $user->id)->update([
            'is_banned' => null
        ]);

        // Returning success API response.
        return $this->success(null, 'User was unbanned successfully.');
    }
}
