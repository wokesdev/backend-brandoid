<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $purchases = Purchase::with('purchase_details')->where('user_id', Auth::id())->get();

        return $this->success($purchases, 'Data retrieved successfully.');
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
            'rincian_akun_pembayaran_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'barang_id.*' => 'required|numeric|exists:items,id',
            'kuantitas.*' => 'required|numeric',
            'harga_satuan.*' => 'required|numeric',
        ]);

        for($i = 0; $i < count((array) $attr['barang_id']); $i++)
        {
            $currentItem = Item::select('user_id')->where('id', $attr['barang_id'][$i])->first();

            if(Auth::id() !== $currentItem->user_id){
                return $this->error('Access is not allowed.', 403);
                break;
            }
        }

        $transaction = DB::transaction(function () use ($attr) {
            $purchase = Purchase::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'nomor_pembelian' => '',
                'total' => 0,
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $total = 0;

            for($i = 0; $i < count((array) $attr['barang_id']); $i++)
            {
                $currentItem = Item::select('stok')->where('id', $attr['barang_id'][$i])->first();
                $subtotal = $attr['kuantitas'][$i] * $attr['harga_satuan'][$i];

                $purchaseDetail = PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'item_id' => $attr['barang_id'][$i],
                    'kuantitas'  => $attr['kuantitas'][$i],
                    'harga_satuan' => $attr['harga_satuan'][$i],
                    'subtotal' => $subtotal,
                ]);

                $updateStock = Item::where('id', $attr['barang_id'][$i])->update([
                    'stok' => $currentItem->stok + $attr['kuantitas'][$i],
                ]);

                $total += $subtotal;
            }

            $updatePurchase = Purchase::where('id', $purchase->id)->update([
                'nomor_pembelian' => 'PC-' . Str::padLeft($purchase->id, 5, '0'),
                'total' => $total,
            ]);

            $insertedPurchase = Purchase::with('purchase_details')->where('id', $purchase->id)->get();

            return $insertedPurchase;
        });

        return $this->success($transaction, 'Data inserted successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
           return $this->error('Access is not allowed.', 403);
        }

        $prchase = Purchase::with('purchase_details')->where('id', $purchase->id)->get();

        return $this->success($prchase, 'Data with that id retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit(Purchase $purchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Purchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
        }

        $attr = $request->validate([
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'rincian_akun_pembayaran_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'barang_id.*' => 'required|numeric|exists:items,id',
            'kuantitas.*' => 'required|numeric',
            'harga_satuan.*' => 'required|numeric',
        ]);

        for($i = 0; $i < count((array) $attr['barang_id']); $i++)
        {
            $currentItem = Item::select('user_id')->where('id', $attr['barang_id'][$i])->first();

            if(Auth::id() !== $currentItem->user_id){
                return $this->error('Access is not allowed.', 403);
                break;
            }
        }

        $transaction = DB::transaction(function () use ($attr, $purchase) {
            $prchase = Purchase::where('id', $purchase->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $total = 0;

            for($i = 0; $i < count((array) $attr['barang_id']); $i++)
            {
                $currentItem = Item::select('stok')->where('id', $attr['barang_id'][$i])->first();
                $currentPurchaseDetail = PurchaseDetail::select('kuantitas')->where('purchase_id', $purchase->id)->where('item_id', $attr['barang_id'][$i])->first();
                $subtotal = $attr['kuantitas'][$i] * $attr['harga_satuan'][$i];

                $purchaseDetail = PurchaseDetail::where('item_id', $attr['barang_id'][$i])->where('purchase_id', $purchase->id)->update([
                    'item_id' => $attr['barang_id'][$i],
                    'kuantitas'  => $attr['kuantitas'][$i],
                    'harga_satuan' => $attr['harga_satuan'][$i],
                    'subtotal' => $subtotal,
                ]);

                $updateStock = Item::where('id', $attr['barang_id'][$i])->update([
                    'stok' => ($currentItem->stok - $currentPurchaseDetail->kuantitas) + $attr['kuantitas'][$i],
                ]);

                $total += $subtotal;
            }

            $updatePurchase = Purchase::where('id', $purchase->id)->update([
                'total' => $total,
            ]);

            $updatedPurchase = Purchase::with('purchase_details')->where('id', $purchase->id)->get();

            return $updatedPurchase;
        });

        return $this->success($transaction, 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(Purchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
           return $this->error('Access is not allowed.', 403);
        }

        if (PurchaseDetail::where('purchase_id', $purchase->id)->count() !== 0) {
            $currentPurchaseDetailCount = PurchaseDetail::where('purchase_id', $purchase->id)->count();

            for ($i = 0; $i < $currentPurchaseDetailCount; $i++) {
                $currentPurchaseDetail = PurchaseDetail::select('item_id', 'kuantitas')->where('purchase_id', $purchase->id)->get();
                $currentItem = Item::select('stok')->where('id', $currentPurchaseDetail[$i]->item_id)->first();

                $destroyStok = Item::where('id', $currentPurchaseDetail[$i]->item_id)->update([
                    'stok' => $currentItem->stok - $currentPurchaseDetail[$i]->kuantitas,
                ]);
            }

            if ($destroyStok) {
                $destroy = Purchase::where('id', $purchase->id)->delete();
            }
        } else if (PurchaseDetail::where('purchase_id', $purchase->id)->count() === 0) {
            $destroy = Purchase::where('id', $purchase->id)->delete();
        }

        return $this->success(null, 'Data deleted successfully.');
    }
}
