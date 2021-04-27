<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CoaDetailController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all chart of account's details.
        $chartOfAccountDetails = ChartOfAccountDetail::all();

        // Returning success API response.
        return $this->success($chartOfAccountDetails, "All chart of account's details retrieved successfully.");
    }

    public function store(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'coa_id' => 'required|numeric|exists:chart_of_accounts,id',
            'nama_rincian_akun' => 'required|string|max:255|unique:chart_of_account_details,nama_rincian_akun',
        ]);

        // Getting all needed variable.
        $nomor_akun = ChartOfAccount::select('nomor_akun')->where('id', $attr['coa_id'])->first();
        $nomor_rincian_akun = ChartOfAccountDetail::where('chart_of_account_id', $attr['coa_id'])->pluck('nomor_rincian_akun')->toArray();
        $other_nomor_akun = ChartOfAccount::select('nomor_akun')->where('nomor_akun', '!=', $nomor_akun->nomor_akun)->where('nomor_akun', '>', $nomor_akun->nomor_akun)->orderBy('nomor_akun', 'asc')->first();

        // Some logic to automatically using chart of account's number for chart of account's detail's number.
        if ($other_nomor_akun !== null) {
            for ($i = $nomor_akun->nomor_akun + 1; $i < $other_nomor_akun->nomor_akun; $i++) {
                if(!in_array($i, $nomor_rincian_akun)){
                    // Creating new chart of account's detail for selected chart of account.
                    $chartOfAccountDetail = ChartOfAccountDetail::create([
                        'chart_of_account_id' => $attr['coa_id'],
                        'nomor_rincian_akun' => $i,
                        'nama_rincian_akun' => $attr['nama_rincian_akun'],
                    ]);

                    // Returning success API response.
                    return $this->success($chartOfAccountDetail, "Chart of account's detail created successfully.");

                    // Break out from the loop.
                    break;
                }
            }
        } else {
            for ($i = $nomor_akun->nomor_akun + 1; $i < 9999; $i++) {
                if(!in_array($i, $nomor_rincian_akun)){
                    // Creating new chart of account's detail for selected chart of account.
                    $chartOfAccountDetail = ChartOfAccountDetail::create([
                        'chart_of_account_id' => $attr['coa_id'],
                        'nomor_rincian_akun' => $i,
                        'nama_rincian_akun' => $attr['nama_rincian_akun'],
                    ]);

                    // Returning success API response.
                    return $this->success($chartOfAccountDetail, "Chart of account's detail created successfully.");

                    // Break out from the loop.
                    break;
                }
            }
        }
    }

    public function show(ChartOfAccountDetail $coaDetail)
    {
        // Getting selected chart of account's detail.
        $currentChartOfAccountDetail = ChartOfAccountDetail::findOrFail($coaDetail->id);

        // Returning success API response.
        return $this->success($currentChartOfAccountDetail, "Chart of account's detail with that id retrieved successfully.");
    }

    public function update(Request $request, ChartOfAccountDetail $coaDetail)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'nomor_rincian_akun' => 'required|numeric|digits_between:1,4|unique:chart_of_account_details,nomor_rincian_akun,' . $coaDetail->id,
            'nama_rincian_akun' => 'required|string|max:255|unique:chart_of_account_details,nama_rincian_akun,' . $coaDetail->id
        ]);

        // Updating selected chart of account's detail.
        $updateChartOfAccountDetail = ChartOfAccountDetail::where('id', $coaDetail->id)->update([
            'nomor_rincian_akun' => $attr['nomor_rincian_akun'],
            'nama_rincian_akun' => $attr['nama_rincian_akun'],
        ]);

        // Returning success API response.
        return $this->success($attr, "Chart of account's detail updated successfully.");
    }

    public function destroy(ChartOfAccountDetail $coaDetail)
    {
        // Deleting selected chart of account's detail.
        $deleteChartOfAccountDetail = ChartOfAccountDetail::where('id', $coaDetail->id)->delete();

        // Returning success API response.
        return $this->success(null, "Chart of account's detail deleted successfully.");
    }
}
