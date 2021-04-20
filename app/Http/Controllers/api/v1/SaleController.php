<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sales = Sale::with('sale_details')->where('user_id', Auth::id())->get();

        return $this->success($sales, 'Data retrieved successfully.');
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
            $sale = Sale::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'nomor_penjualan' => '',
                'total' => 0,
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $total = 0;

            for($i = 0; $i < count((array) $attr['barang_id']); $i++)
            {
                $currentItem = Item::select('stok')->where('id', $attr['barang_id'][$i])->first();
                $subtotal = $attr['kuantitas'][$i] * $attr['harga_satuan'][$i];

                $saleDetail = SaleDetail::create([
                    'sale_id' => $sale->id,
                    'item_id' => $attr['barang_id'][$i],
                    'kuantitas'  => $attr['kuantitas'][$i],
                    'harga_satuan' => $attr['harga_satuan'][$i],
                    'subtotal' => $subtotal,
                ]);

                $updateStock = Item::where('id', $attr['barang_id'][$i])->update([
                    'stok' => $currentItem->stok - $attr['kuantitas'][$i],
                ]);

                $total += $subtotal;
            }

            $updateSale = Sale::where('id', $sale->id)->update([
                'nomor_penjualan' => 'SL-' . Str::padLeft($sale->id, 5, '0'),
                'total' => $total,
            ]);

            $insertedSale = Sale::with('sale_details')->where('id', $sale->id)->get();

            return $insertedSale;
        });

        return $this->success($transaction, 'Data inserted successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $sale)
    {
        if ($sale->user_id !== Auth::id()) {
           return $this->error('Access is not allowed.', 403);
        }

        $sle = Sale::with('sale_details')->where('id', $sale->id)->get();

        return $this->success($sle, 'Data with that id retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $sale)
    {
        if ($sale->user_id !== Auth::id()) {
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

        $transaction = DB::transaction(function () use ($attr, $sale) {
            $sle = Sale::where('id', $sale->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            $total = 0;

            for($i = 0; $i < count((array) $attr['barang_id']); $i++)
            {
                $currentItem = Item::select('stok')->where('id', $attr['barang_id'][$i])->first();
                $currentSaleDetail = SaleDetail::select('kuantitas')->where('sale_id', $sale->id)->where('item_id', $attr['barang_id'][$i])->first();
                $subtotal = $attr['kuantitas'][$i] * $attr['harga_satuan'][$i];

                $saleDetail = SaleDetail::where('item_id', $attr['barang_id'][$i])->where('sale_id', $sale->id)->update([
                    'item_id' => $attr['barang_id'][$i],
                    'kuantitas'  => $attr['kuantitas'][$i],
                    'harga_satuan' => $attr['harga_satuan'][$i],
                    'subtotal' => $subtotal,
                ]);

                $updateStock = Item::where('id', $attr['barang_id'][$i])->update([
                    'stok' => ($currentItem->stok + $currentSaleDetail->kuantitas) - $attr['kuantitas'][$i],
                ]);

                $total += $subtotal;
            }

            $updateSale = Sale::where('id', $sale->id)->update([
                'total' => $total,
            ]);

            $updatedSale = Sale::with('sale_details')->where('id', $sale->id)->get();

            return $updatedSale;
        });

        return $this->success($transaction, 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sale $sale)
    {
        if ($sale->user_id !== Auth::id()) {
            return $this->error('Access is not allowed.', 403);
         }

         if (SaleDetail::where('sale_id', $sale->id)->count() !== 0) {
             $currentSaleDetailCount = SaleDetail::where('sale_id', $sale->id)->count();

             for ($i = 0; $i < $currentSaleDetailCount; $i++) {
                 $currentSaleDetail = SaleDetail::select('item_id', 'kuantitas')->where('sale_id', $sale->id)->get();
                 $currentItem = Item::select('stok')->where('id', $currentSaleDetail[$i]->item_id)->first();

                 $destroyStok = Item::where('id', $currentSaleDetail[$i]->item_id)->update([
                     'stok' => $currentItem->stok + $currentSaleDetail[$i]->kuantitas,
                 ]);
             }

             if ($destroyStok) {
                 $destroy = Sale::where('id', $sale->id)->delete();
             }
         } else if (SaleDetail::where('sale_id', $sale->id)->count() === 0) {
             $destroy = Sale::where('id', $sale->id)->delete();
         }

         return $this->success(null, 'Data deleted successfully.');
    }
}
