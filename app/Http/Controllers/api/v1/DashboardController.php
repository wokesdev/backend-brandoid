<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function userCount()
    {
        // Counting total user, except admin.
        $users = User::where('is_admin', '!=', '1')->count();

        // Returning success API response.
        return $this->success($users, 'Users counted sucessfully.');
    }
}
