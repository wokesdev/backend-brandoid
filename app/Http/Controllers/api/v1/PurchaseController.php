<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\GeneralEntry;
use App\Models\GeneralEntryDetail;
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
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all purchases along with purchase's details and general entry.
        $purchases = Purchase::with(['purchase_details', 'general_entry'])->where('user_id', Auth::id())->get();

        // Returning success API response.
        return $this->success($purchases, 'All purchases was retrieved successfully.');
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

            if(Auth::id() != $currentItem->user_id){
                return $this->error('Access was not allowed.', 403);
            }
        }

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr) {
            // Creating new purchase.
            $purchase = Purchase::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'nomor_pembelian' => '',
                'total' => 0,
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Creating new purchase's details for the new purchase and updating items' stock.
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
                    'stok' => $attr['kuantitas'][$i],
                ]);

                $updateStock = Item::where('id', $attr['barang_id'][$i])->update([
                    'stok' => $currentItem->stok + $attr['kuantitas'][$i],
                ]);

                $total += $subtotal;
            }

            // Updating purchase's number and amount for the new purchase.
            $updatePurchase = Purchase::where('id', $purchase->id)->update([
                'nomor_pembelian' => 'PC-' . Str::padLeft($purchase->id, 5, '0'),
                'total' => $total,
            ]);

            // Creating new general entry for the new purchase.
            $generalEntry = GeneralEntry::create([
                'user_id' => Auth::id(),
                'purchase_id' => $purchase->id,
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
                'coa_detail_id' => $attr['rincian_akun_id'],
                'debit' => $total,
                'kredit' => 0,
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::create([
                'general_entry_id' => $generalEntry->id,
                'coa_detail_id' => $attr['rincian_akun_pembayaran_id'],
                'debit' => 0,
                'kredit' => $total,
            ]);

            // Getting and returning the new purchase along with purchase's details and general entry.
            $insertedPurchase = Purchase::with(['purchase_details', 'general_entry'])->where('id', $purchase->id)->get();

            return $insertedPurchase;
        });

        // Returning success API response.
        return $this->success($transaction, 'Purchase was created successfully.');
    }

    public function show(Purchase $purchase)
    {
        // Validating selected purchase for authenticated user.
        if ($purchase->user_id != Auth::id()) {
           return $this->error('Access was not allowed.', 403);
        }

        // Getting selected purchase along with purchase's details and general entry.
        $prchase = Purchase::with(['purchase_details', 'general_entry'])->where('id', $purchase->id)->get();

        // Returning success API response.
        return $this->success($prchase, 'Purchase with that id retrieved successfully.');
    }

    public function update(Request $request, Purchase $purchase)
    {
        // Validating selected purchase for authenticated user.
        if ($purchase->user_id != Auth::id()) {
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

            if(Auth::id() != $currentItem->user_id){
                return $this->error('Access was not allowed.', 403);
            }
        }

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr, $purchase) {
            // Updating selected purchase.
            $updatePurchase = Purchase::where('id', $purchase->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'coa_detail_payment_id' => $attr['rincian_akun_pembayaran_id'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating purchase's details for selected purchase and updating items' stock.
            $total = 0;

            for($i = 0; $i < count((array) $attr['barang_id']); $i++)
            {
                $currentItem = Item::select('stok')->where('id', $attr['barang_id'][$i])->first();
                $currentPurchaseDetail = PurchaseDetail::select('kuantitas', 'stok')->where('purchase_id', $purchase->id)->where('item_id', $attr['barang_id'][$i])->first();
                $subtotal = $attr['kuantitas'][$i] * $attr['harga_satuan'][$i];

                if ($attr['kuantitas'][$i] < $currentPurchaseDetail->kuantitas) {
                    $difference = $currentPurchaseDetail->kuantitas - $attr['kuantitas'][$i];
                    $newStock = $currentPurchaseDetail->stok - $difference;
                } else if ($attr['kuantitas'][$i] > $currentPurchaseDetail->kuantitas) {
                    $difference = $attr['kuantitas'][$i] - $currentPurchaseDetail->kuantitas;
                    $newStock = $currentPurchaseDetail->stok + $difference;
                } else {
                    $newStock = $currentPurchaseDetail->stok;
                }

                $purchaseDetail = PurchaseDetail::where('item_id', $attr['barang_id'][$i])->where('purchase_id', $purchase->id)->update([
                    'item_id' => $attr['barang_id'][$i],
                    'kuantitas'  => $attr['kuantitas'][$i],
                    'harga_satuan' => $attr['harga_satuan'][$i],
                    'subtotal' => $subtotal,
                    'stok' => $newStock,
                ]);

                $updateStock = Item::where('id', $attr['barang_id'][$i])->update([
                    'stok' => ($currentItem->stok - $currentPurchaseDetail->kuantitas) + $attr['kuantitas'][$i],
                ]);

                $total += $subtotal;
            }

            // Updating purchase's amount for selected purchase.
            $updatePurchase = Purchase::where('id', $purchase->id)->update([
                'total' => $total,
            ]);

            // Updating general entry for selected purchase.
            $generalEntry = GeneralEntry::where('purchase_id', $purchase->id)->update([
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating general entry's details for selected purchase.
            $generalEntryDetailDebit = GeneralEntryDetail::where('purchase_id', $purchase->id)->where('kredit', 0)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'debit' => $total,
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::where('purchase_id', $purchase->id)->where('debit', 0)->update([
                'coa_detail_id' => $attr['rincian_akun_pembayaran_id'],
                'kredit' => $total,
            ]);

            // Getting and returning updated purchase along with purchase's details and general entry.
            $updatedPurchase = Purchase::with(['purchase_details', 'general_entry'])->where('id', $purchase->id)->get();

            return $updatedPurchase;
        });

        // Returning success API response.
        return $this->success($transaction, 'Purchase was updated successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        // Validating selected purchase for authenticated user.
        if ($purchase->user_id != Auth::id()) {
           return $this->error('Access was not allowed.', 403);
        }

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($purchase) {
            // Counting number of purchase's details for selected purchase.
            $currentPurchaseDetailsCount = PurchaseDetail::where('purchase_id', $purchase->id)->count();

            // Updating items' stock.
            for ($i = 0; $i < $currentPurchaseDetailsCount; $i++) {
                $currentPurchaseDetail = PurchaseDetail::select('item_id', 'kuantitas')->where('purchase_id', $purchase->id)->get();
                $currentItem = Item::select('stok')->where('id', $currentPurchaseDetail[$i]->item_id)->first();

                $updateStock = Item::where('id', $currentPurchaseDetail[$i]->item_id)->update([
                    'stok' => $currentItem->stok - $currentPurchaseDetail[$i]->kuantitas,
                ]);
            }

            // Deleting selected purchase.
            $deletePurchase = Purchase::where('id', $purchase->id)->delete();
        });

        // Returning success API response.
        return $this->success(null, 'Purchase was deleted successfully.');
    }
}
