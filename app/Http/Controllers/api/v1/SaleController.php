<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccountDetail;
use App\Models\GeneralEntry;
use App\Models\GeneralEntryDetail;
use App\Models\Item;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all sales along with sale's details and general entry.
        $sales = Sale::with(['sale_details', 'general_entry'])->where('user_id', Auth::id())->get();

        // Returning success API response.
        return $this->success($sales, 'All sales was retrieved successfully.');
    }

    public function store(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'rincian_akun_pembayaran_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'barang_id.*' => 'required|numeric|exists:items,id',
            'kuantitas.*' => 'required|numeric',
            'harga_satuan.*' => 'required|numeric',
        ]);

        // Validating selected items for authenticated user.
        for($i = 0; $i < count((array) $attr['barang_id']); $i++)
        {
            $currentItem = Item::select('user_id')->where('id', $attr['barang_id'][$i])->first();

            if(Auth::id() !== $currentItem->user_id){
                return $this->error('Access was not allowed.', 403);
            }
        }

        // Validating items' stocks.
        for($i = 0; $i < count((array) $attr['barang_id']); $i++)
        {
            $currentItem = Item::select('id', 'nama_barang', 'stok')->where('id', $attr['barang_id'][$i])->first();
            $qty = $attr['kuantitas'][$i];

            if (!($qty <= $currentItem->stok)) {
                return $this->error('Stock is not enough, stock for ' . $currentItem->nama_barang . ' is only ' . $currentItem->stok, 422);
            }
        }

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr) {
            // Creating new sale.
            $sale = Sale::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'nomor_penjualan' => '',
                'total' => 0,
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Creating new sale's details for the new sale and updating items' stock.
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

            // Updating sale's number and amount for the new sale.
            $updateSale = Sale::where('id', $sale->id)->update([
                'nomor_penjualan' => 'SL-' . Str::padLeft($sale->id, 5, '0'),
                'total' => $total,
            ]);

            // Creating new general entry for the new sale.
            $generalEntry = GeneralEntry::create([
                'user_id' => Auth::id(),
                'sale_id' => $sale->id,
                'nomor_transaksi' => '',
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating transaction's number for the new general entry.
            $updateGeneralEntry = GeneralEntry::where('id', $generalEntry->id)->update([
                'nomor_transaksi' => $generalEntry->id,
            ]);

            // Creating new general entry's details for the new general entry.
            $generalEntryDetailDebit = GeneralEntryDetail::create([
                'general_entry_id' => $generalEntry->id,
                'coa_detail_id' => $attr['rincian_akun_pembayaran_id'],
                'debit' => $total,
                'kredit' => 0,
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::create([
                'general_entry_id' => $generalEntry->id,
                'coa_detail_id' => $attr['rincian_akun_id'],
                'debit' => 0,
                'kredit' => $total,
            ]);

            // Getting and returning the new sale along with sale's details and general entry.
            $insertedSale = Sale::with(['sale_details', 'general_entry'])->where('id', $sale->id)->get();

            return $insertedSale;
        });

        // Returning success API response.
        return $this->success($transaction, 'Sale was created successfully.');
    }

    public function show(Sale $sale)
    {
        // Validating selected sale for authenticated user.
        if ($sale->user_id !== Auth::id()) {
           return $this->error('Access was not allowed.', 403);
        }

        // Getting selected sale along with sale's details and general entry.
        $sle = Sale::with('sale_details')->where('id', $sale->id)->get();

        // Returning success API response.
        return $this->success($sle, 'Sale with that id was retrieved successfully.');
    }

    public function update(Request $request, Sale $sale)
    {
        // Validating selected sale for authenticated user.
        if ($sale->user_id !== Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Validating incoming request.
        $attr = $request->validate([
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'rincian_akun_pembayaran_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'barang_id.*' => 'required|numeric|exists:items,id',
            'kuantitas.*' => 'required|numeric',
            'harga_satuan.*' => 'required|numeric',
        ]);

        // Validating selected items for authenticated user.
        for($i = 0; $i < count((array) $attr['barang_id']); $i++)
        {
            $currentItem = Item::select('user_id')->where('id', $attr['barang_id'][$i])->first();

            if(Auth::id() !== $currentItem->user_id){
                return $this->error('Access was not allowed.', 403);
            }
        }

        // Validating items' stocks.
        for($i = 0; $i < count((array) $attr['barang_id']); $i++)
        {
            $currentItem = Item::select('id', 'nama_barang', 'stok')->where('id', $attr['barang_id'][$i])->first();
            $qty = $attr['kuantitas'][$i];

            if (!($qty <= $currentItem->stok)) {
                return $this->error('Stock is not enough, stock for ' . $currentItem->nama_barang . ' is only ' . $currentItem->stok, 422);
            }
        }

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr, $sale) {
            // Updating selected sale.
            $updateSale = Sale::where('id', $sale->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating sale's details for selected sale and updating items' stock.
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

            // Updating sale's amount for selected sale.
            $updateSale = Sale::where('id', $sale->id)->update([
                'total' => $total,
            ]);

            // Getting cash and stock from chart of account's detail.
            $hppOnCoa = ChartOfAccountDetail::select('id')->where('nama_rincian_akun', 'Harga Pokok Penjualan')->first();
            $stockOnCoa = ChartOfAccountDetail::select('id')->where('nama_rincian_akun', 'Persediaan Barang Dagang')->first();

            // Updating general entry for selected sale.
            $generalEntry = GeneralEntry::where('sale_id', $sale->id)->update([
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating general entry's details for selected sale.
            $generalEntryDetailDebit = GeneralEntryDetail::where('sale_id', $sale->id)->where('kredit', 0)->where('chart_of_account_detail_id', '!=', $hppOnCoa->id)->update([
                'coa_detail_id' => $attr['rincian_akun_pembayaran_id'],
                'debit' => $total,
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::where('sale_id', $sale->id)->where('debit', 0)->where('chart_of_account_detail_id', '!=', $stockOnCoa->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'kredit' => $total,
            ]);

            // Getting and returning updated sale along with sale's details and general entry.
            $updatedSale = Sale::with('sale_details')->where('id', $sale->id)->get();

            return $updatedSale;
        });

        // Returning success API response.
        return $this->success($transaction, 'Sale was updated successfully.');
    }

    public function destroy(Sale $sale)
    {
        // Validating selected sale for authenticated user.
        if ($sale->user_id !== Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($sale) {
            // Counting number of sale's details for selected sale.
            $currentSaleDetailCount = SaleDetail::where('sale_id', $sale->id)->count();

            // Updating items' stock.
            for ($i = 0; $i < $currentSaleDetailCount; $i++) {
                $currentSaleDetail = SaleDetail::select('item_id', 'kuantitas')->where('sale_id', $sale->id)->get();
                $currentItem = Item::select('stok')->where('id', $currentSaleDetail[$i]->item_id)->first();

                $updateStock = Item::where('id', $currentSaleDetail[$i]->item_id)->update([
                    'stok' => $currentItem->stok + $currentSaleDetail[$i]->kuantitas,
                ]);
            }

            // Deleting selected sale.
            $deleteSale = Sale::where('id', $sale->id)->delete();
        });

        // Returning success API response
        return $this->success(null, 'Sale was deleted successfully.');
    }
}
