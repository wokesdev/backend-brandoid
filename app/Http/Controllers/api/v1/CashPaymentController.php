<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\CashPayment;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashPaymentController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cashPayments = CashPayment::where('user_id', Auth::id())->get();

        return $this->success($cashPayments, 'Data retrieved successfully.');
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
            $cshPayment = CashPayment::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nomor_nota' => '',
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $updateCashPayment = CashPayment::where('id', $cshPayment->id)->update([
                'nomor_nota' => $cshPayment->id,
            ]);

            $insertedCashPayment = CashPayment::where('id', $cshPayment->id)->get();

            return $insertedCashPayment;
        });

        return $this->success($transaction, 'Data inserted successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CashPayment  $cashPayment
     * @return \Illuminate\Http\Response
     */
    public function show(CashPayment $cashPayment)
    {
        if ($cashPayment->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
        }

        $cshPayment = CashPayment::findOrFail($cashPayment->id);

        return $this->success($cshPayment, 'Data with that id retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CashPayment  $cashPayment
     * @return \Illuminate\Http\Response
     */
    public function edit(CashPayment $cashPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CashPayment  $cashPayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CashPayment $cashPayment)
    {
        if ($cashPayment->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
        }

        $attr = $request->validate([
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal' => 'required|numeric',
        ]);

        $transaction = DB::transaction(function () use ($attr, $cashPayment) {
            $cshPayment = CashPayment::where('id', $cashPayment->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $updatedCashPayment = CashPayment::where('id', $cashPayment->id)->get();

            return $updatedCashPayment;
        });

        return $this->success($transaction, 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CashPayment  $cashPayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(CashPayment $cashPayment)
    {
        if ($cashPayment->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
        }

        $cshPayment = CashPayment::where('id', $cashPayment->id)->delete();

        return $this->success(null, 'Data deleted successfully.');
    }
}
