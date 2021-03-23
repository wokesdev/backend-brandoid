<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CoaController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $chartOfAccounts = ChartOfAccount::all();

        return $this->success($chartOfAccounts, 'Data retrieved successfully');
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
            'nomor_akun' => 'required|numeric|digits_between:1,4|unique:chart_of_accounts,nomor_akun',
            'nama_akun' => 'required|string|max:255|unique:chart_of_accounts,nama_akun'
        ]);

        $chartOfAccount = ChartOfAccount::create([
            'nomor_akun' => $attr['nomor_akun'],
            'nama_akun' => $attr['nama_akun']
        ]);

        return $this->success($chartOfAccount, 'Data inserted successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChartOfAccount  $coa
     * @return \Illuminate\Http\Response
     */
    public function show(ChartOfAccount $coa)
    {
        $chartOfAccount = ChartOfAccount::findOrFail($coa->id);

        return $this->success($chartOfAccount, 'Data with that id retrieved successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChartOfAccount  $coa
     * @return \Illuminate\Http\Response
     */
    public function edit(ChartOfAccount $coa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ChartOfAccount  $coa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ChartOfAccount $coa)
    {
        $attr = $request->validate([
            'nomor_akun' => 'required|numeric|digits_between:1,4|unique:chart_of_accounts,nomor_akun,' . $coa->id,
            'nama_akun' => 'required|string|max:255|unique:chart_of_accounts,nama_akun,' . $coa->id
        ]);

        $chartOfAccount = ChartOfAccount::firstOrFail('id', $coa->id)->update([
            'nomor_akun' => $attr['nomor_akun'],
            'nama_akun' => $attr['nama_akun']
        ]);

        return $this->success($attr, 'Data updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChartOfAccount  $coa
     * @return \Illuminate\Http\Response
     */
    public function destroy(ChartOfAccount $coa)
    {
        $chartOfAccount = ChartOfAccount::firstOrFail('id', $coa->id)->delete();

        return $this->success(null, 'Data deleted successfully');
    }
}
