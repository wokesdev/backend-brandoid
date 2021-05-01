<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\CashReceipt;
use App\Models\ChartOfAccountDetail;
use App\Models\GeneralEntry;
use App\Models\GeneralEntryDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashReceiptController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all cash receipts along with cash receipt's general entry.
        $cashReceipts = CashReceipt::with('general_entry')->where('user_id', Auth::id())->get();

        // Returning success API response.
        return $this->success($cashReceipts, 'All cash receipts was retrieved successfully.');
    }

    public function store(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal' => 'required|numeric',
        ]);

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr) {
            // Creating new cash receipt.
            $cashReceipt = CashReceipt::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nomor_nota' => '',
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating cash receipt's note number for the new cash receipt.
            $updateCashReceipt = CashReceipt::where('id', $cashReceipt->id)->update([
                'nomor_nota' => $cashReceipt->id,
            ]);

            // Getting cash from chart of account's detail.
            $cashOnCoa = ChartOfAccountDetail::select('id')->where('nama_rincian_akun', 'Kas')->first();

            // Creating new general entry for the new cash receipt.
            $generalEntry = GeneralEntry::create([
                'user_id' => Auth::id(),
                'cash_receipt_id' => $cashReceipt->id,
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
                'debit' => $attr['nominal'],
                'kredit' => 0,
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::create([
                'general_entry_id' => $generalEntry->id,
                'coa_detail_id' => $cashOnCoa->id,
                'debit' => 0,
                'kredit' => $attr['nominal'],
            ]);

            $insertedCashReceipt = CashReceipt::with('general_entry')->where('id', $cashReceipt->id)->get();

            return $insertedCashReceipt;
        });

        // Returning success API response.
        return $this->success($transaction, 'Cash receipt was created successfully.');
    }

    public function show(CashReceipt $cashReceipt)
    {
        // Validating selected cash receipt for authenticated user.
        if ($cashReceipt->user_id !== Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Getting selected cash receipt along with cash receipt's general entry.
        $currentCashReceipt = CashReceipt::findOrFail($cashReceipt->id);

        // Returning success API response.
        return $this->success($currentCashReceipt, 'Data with that id was retrieved successfully.');
    }

    public function update(Request $request, CashReceipt $cashReceipt)
    {
        // Validating selected cash payment for authenticated user.
        if ($cashReceipt->user_id !== Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Validating incoming request.
        $attr = $request->validate([
            'rincian_akun_id' => 'required|numeric|exists:chart_of_account_details,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal' => 'required|numeric',
        ]);

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr, $cashReceipt) {
            // Updating selected cash receipt.
            $updateCashReceipt = CashReceipt::where('id', $cashReceipt->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating general entry for selected cash receipt.
            $generalEntry = GeneralEntry::where('cash_receipt_id', $cashReceipt->id)->update([
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating general entry's details for selected cash receipt.
            $generalEntryDetailDebit = GeneralEntryDetail::where('cash_receipt_id', $cashReceipt->id)->where('kredit', 0)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'debit' => $attr['nominal'],
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::where('cash_receipt_id', $cashReceipt->id)->where('debit', 0)->update([
                'kredit' => $attr['nominal'],
            ]);

            // Getting and returning updated cash receipt along with cash payment's general entry.
            $updatedCashReceipt = CashReceipt::where('id', $cashReceipt->id)->get();

            return $updatedCashReceipt;
        });

        // Returning success API response.
        return $this->success($transaction, 'Data updated was successfully.');
    }

    public function destroy(CashReceipt $cashReceipt)
    {
        // Validating selected cash payment for authenticated user.
        if ($cashReceipt->user_id !== Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Deleting seleted cash payment.
        $deleteCashReceipt = CashReceipt::where('id', $cashReceipt->id)->delete();

        // Returning success API response.
        return $this->success(null, 'Data deleted was successfully.');
    }
}
