<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CoaController extends Controller
{
    // Using ApiResponser's traits.
    use ApiResponser;

    public function index()
    {
        // Getting all chart of accounts.
        $chartOfAccounts = ChartOfAccount::all();

        // Returning success API response.
        return $this->success($chartOfAccounts, 'All chart of accounts was retrieved successfully.');
    }

    public function store(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'nomor_akun' => 'required|numeric|digits_between:1,4|unique:chart_of_accounts,nomor_akun',
            'nama_akun' => 'required|string|max:255|unique:chart_of_accounts,nama_akun'
        ]);

        // Creating new chart of account.
        $chartOfAccount = ChartOfAccount::create([
            'nomor_akun' => $attr['nomor_akun'],
            'nama_akun' => $attr['nama_akun']
        ]);

        // Returning success API response.
        return $this->success($chartOfAccount, 'Chart of account was created successfully.');
    }

    public function show(ChartOfAccount $coa)
    {
        // Getting selected chart of account.
        $currentChartOfAccount = ChartOfAccount::with('coa_details')->where('id', $coa->id)->get();

        // Returning success API response.
        return $this->success($currentChartOfAccount, 'Chart of account with that id was retrieved successfully.');
    }

    public function update(Request $request, ChartOfAccount $coa)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'nomor_akun' => 'required|numeric|digits_between:1,4|unique:chart_of_accounts,nomor_akun,' . $coa->id,
            'nama_akun' => 'required|string|max:255|unique:chart_of_accounts,nama_akun,' . $coa->id
        ]);

        // Updating selected chart of account.
        $updateChartOfAccount = ChartOfAccount::where('id', $coa->id)->update([
            'nomor_akun' => $attr['nomor_akun'],
            'nama_akun' => $attr['nama_akun']
        ]);

        // Returning success API response.
        return $this->success($attr, 'Chart of account was updated successfully.');
    }

    public function destroy(ChartOfAccount $coa)
    {
        // Deleting selected chart of account.
        $deleteChartOfAccount = ChartOfAccount::where('id', $coa->id)->delete();

        // Returning success API response.
        return $this->success(null, 'Chart of account was deleted successfully.');
    }
}
