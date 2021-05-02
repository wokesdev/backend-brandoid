<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\CashPayment;
use App\Models\CashReceipt;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function userCount()
    {
        // Counting total users, except admin.
        $users = User::where('is_admin', null)->count();

        // Returning success API response.
        return $this->success($users, 'Users was counted sucessfully.');
    }

    public function adminCount()
    {
        // Counting total admins.
        $admins = User::where('is_admin', 1)->count();

        // Returning success API response.
        return $this->success($admins, 'Admins was counted sucessfully.');
    }

    public function bannedCount()
    {
        // Counting total banned users.
        $banneds = User::where('is_banned', 1)->count();

        // Returning success API response.
        return $this->success($banneds, 'Banned users was counted sucessfully.');
    }

    public function purchaseCount()
    {
        // Counting total purchases by user.
        $purchases = Purchase::where('user_id', Auth::id())->count();

        // Returning success API response.
        return $this->success($purchases, 'Purchases was counted sucessfully.');
    }

    public function saleCount()
    {
        // Counting total sales by user.
        $sales = Sale::where('user_id', Auth::id())->count();

        // Returning success API response.
        return $this->success($sales, 'Sales was counted sucessfully.');
    }

    public function cashPaymentCount()
    {
        // Counting total cash payments by user.
        $cashPayment = CashPayment::where('user_id', Auth::id())->count();

        // Returning success API response.
        return $this->success($cashPayment, 'Cash payments was counted sucessfully.');
    }

    public function cashReceiptCount()
    {
        // Counting total cash receipts by user.
        $cashReceipts = CashReceipt::where('user_id', Auth::id())->count();

        // Returning success API response.
        return $this->success($cashReceipts, 'Cash payments was counted sucessfully.');
    }
}
