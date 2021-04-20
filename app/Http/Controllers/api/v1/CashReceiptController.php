<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\CashReceipt;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashReceiptController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cashReceipts = CashReceipt::where('user_id', Auth::id())->get();

        return $this->success($cashReceipts, 'Data retrieved successfully.');
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
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal' => 'required|numeric',
        ]);

        $transaction = DB::transaction(function () use ($attr) {
            $cshReceipt = CashReceipt::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nomor_nota' => '',
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $updateCashReceipt = CashReceipt::where('id', $cshReceipt->id)->update([
                'nomor_nota' => $cshReceipt->id,
            ]);

            $insertedCashReceipt = CashReceipt::where('id', $cshReceipt->id)->get();

            return $insertedCashReceipt;
        });

        return $this->success($transaction, 'Data inserted successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function show(CashReceipt $cashReceipt)
    {
        if ($cashReceipt->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
        }

        $cshReceipt = CashReceipt::findOrFail($cashReceipt->id);

        return $this->success($cshReceipt, 'Data with that id retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function edit(CashReceipt $cashReceipt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CashReceipt $cashReceipt)
    {
        if ($cashReceipt->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
        }

        $attr = $request->validate([
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal' => 'required|numeric',
        ]);

        $transaction = DB::transaction(function () use ($attr, $cashReceipt) {
            $cshReceipt = CashReceipt::where('id', $cashReceipt->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $updatedCashReceipt = CashReceipt::where('id', $cashReceipt->id)->get();

            return $updatedCashReceipt;
        });

        return $this->success($transaction, 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(CashReceipt $cashReceipt)
    {
        if ($cashReceipt->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
        }

        $cshReceipt = CashReceipt::where('id', $cashReceipt->id)->delete();

        return $this->success(null, 'Data deleted successfully.');
    }
}
