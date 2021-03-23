<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CoaDetailController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $chartOfAccountDetails = ChartOfAccountDetail::all();

        return $this->success($chartOfAccountDetails, 'Data retrieved successfully');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attr = $request->validate([
            'coa_id' => 'required|numeric|exists:chart_of_accounts,id',
            'nama_rincian_akun' => 'required|string|max:255|unique:chart_of_account_details,nama_rincian_akun',
        ]);

        $nomor_akun = ChartOfAccount::select('nomor_akun')->where('id', $attr['coa_id'])->first();
        $nomor_rincian_akun = ChartOfAccountDetail::where('chart_of_account_id', $attr['coa_id'])->pluck('nomor_rincian_akun')->toArray();
        $other_nomor_akun = ChartOfAccount::select('nomor_akun')->where('nomor_akun', '!=', $nomor_akun->nomor_akun)->where('nomor_akun', '>', $nomor_akun->nomor_akun)->orderBy('nomor_akun', 'asc')->first();

        if ($other_nomor_akun !== null) {
            for ($i = $nomor_akun->nomor_akun + 1; $i < $other_nomor_akun->nomor_akun; $i++) {
                if(!in_array($i, $nomor_rincian_akun)){
                    $chartOfAccountDetail = ChartOfAccountDetail::create([
                        'chart_of_account_id' => $attr['coa_id'],
                        'nomor_rincian_akun' => $i,
                        'nama_rincian_akun' => $attr['nama_rincian_akun'],
                    ]);
                    return $this->success($chartOfAccountDetail, 'Data inserted successfully');
                    break;
                }
            }
        } else {
            for ($i = $nomor_akun->nomor_akun + 1; $i < 9999; $i++) {
                if(!in_array($i, $nomor_rincian_akun)){
                    $chartOfAccountDetail = ChartOfAccountDetail::create([
                        'chart_of_account_id' => $attr['coa_id'],
                        'nomor_rincian_akun' => $i,
                        'nama_rincian_akun' => $attr['nama_rincian_akun'],
                    ]);
                    return $this->success($chartOfAccountDetail, 'Data inserted successfully');
                    break;
                }
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChartOfAccountDetail  $coaDetail
     * @return \Illuminate\Http\Response
     */
    public function show(ChartOfAccountDetail $coaDetail)
    {
        $chartOfAccountDetail = ChartOfAccountDetail::findOrFail($coaDetail->id);

        return $this->success($chartOfAccountDetail, 'Data with that id retrieved successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChartOfAccountDetail  $coaDetail
     * @return \Illuminate\Http\Response
     */
    public function edit(ChartOfAccountDetail $coaDetail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ChartOfAccountDetail  $coaDetail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ChartOfAccountDetail $coaDetail)
    {
        $attr = $request->validate([
            'nomor_rincian_akun' => 'required|numeric|digits_between:1,4|unique:chart_of_account_details,nomor_rincian_akun,' . $coaDetail->id,
            'nama_rincian_akun' => 'required|string|max:255|unique:chart_of_account_details,nama_rincian_akun,' . $coaDetail->id
        ]);

        $chartOfAccountDetail = ChartOfAccountDetail::firstOrFail('id', $coaDetail->id)->update([
            'nomor_rincian_akun' => $attr['nomor_rincian_akun'],
            'nama_rincian_akun' => $attr['nama_rincian_akun'],
        ]);

        return $this->success($attr, 'Data updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChartOfAccountDetail  $coaDetail
     * @return \Illuminate\Http\Response
     */
    public function destroy(ChartOfAccountDetail $coaDetail)
    {
        $chartOfAccountDetail = ChartOfAccountDetail::firstOrFail('id', $coaDetail->id)->delete();

        return $this->success(null, 'Data deleted successfully');
    }
}
