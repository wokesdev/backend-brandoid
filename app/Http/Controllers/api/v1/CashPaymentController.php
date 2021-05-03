<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\CashPayment;
use App\Models\ChartOfAccountDetail;
use App\Models\GeneralEntry;
use App\Models\GeneralEntryDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashPaymentController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all cash payments along with cash payment's general entry.
        $cashPayments = CashPayment::with(['coa_detail', 'general_entry'])->where('user_id', Auth::id())->get();

        // Returning success API response.
        return $this->success($cashPayments, 'All cash payments was retrieved successfully.');
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
            // Creating new cash payment.
            $cashPayment = CashPayment::create([
                'user_id' => Auth::id(),
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nomor_nota' => '',
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating cash payment's note number for the new cash payment.
            $updateCashPayment = CashPayment::where('id', $cashPayment->id)->update([
                'nomor_nota' => $cashPayment->id,
            ]);

            // Getting cash from chart of account's detail.
            $cashOnCoa = ChartOfAccountDetail::select('id')->where('nama_rincian_akun', 'Kas')->first();

            // Creating new general entry for the new cash payment.
            $generalEntry = GeneralEntry::create([
                'user_id' => Auth::id(),
                'cash_payment_id' => $cashPayment->id,
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

            // Getting and returning the new cash payment along with cash payment's general entry.
            $insertedCashPayment = CashPayment::with(['coa_detail', 'general_entry'])->where('id', $cashPayment->id)->get();

            return $insertedCashPayment;
        });

        // Returning success API response.
        return $this->success($transaction, 'Cash payment was created successfully.');
    }

    public function show(CashPayment $cashPayment)
    {
        // Validating selected cash payment for authenticated user.
        if ($cashPayment->user_id != Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Getting selected cash payment along with cash payment's general entry.
        $currentCashPayment = CashPayment::with(['coa_detail', 'general_entry'])->findOrFail($cashPayment->id);

        // Returning success API response.
        return $this->success($currentCashPayment, 'Cash payment with that id was retrieved successfully.');
    }

    public function update(Request $request, CashPayment $cashPayment)
    {
        // Validating selected cash payment for authenticated user.
        if ($cashPayment->user_id != Auth::id()) {
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
        $transaction = DB::transaction(function () use ($attr, $cashPayment) {
            // Updating selected cash payment.
            $updateCashPayment = CashPayment::where('id', $cashPayment->id)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'nominal' => $attr['nominal'],
                'keterangan' => $attr['keterangan'],
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating general entry for selected cash payment.
            $generalEntry = GeneralEntry::where('cash_payment_id', $cashPayment->id)->update([
                'tanggal' => $attr['tanggal'],
            ]);

            // Updating general entry's details for selected cash payment.
            $generalEntryDetailDebit = GeneralEntryDetail::where('cash_payment_id', $cashPayment->id)->where('kredit', 0)->update([
                'coa_detail_id' => $attr['rincian_akun_id'],
                'debit' => $attr['nominal'],
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::where('cash_payment_id', $cashPayment->id)->where('debit', 0)->update([
                'kredit' => $attr['nominal'],
            ]);

            // Getting and returning updated cash payment along with cash payment's general entry.
            $updatedCashPayment = CashPayment::with(['coa_detail', 'general_entry'])->where('id', $cashPayment->id)->get();

            return $updatedCashPayment;
        });

        // Returning success API response.
        return $this->success($transaction, 'Cash payment was updated successfully.');
    }

    public function destroy(CashPayment $cashPayment)
    {
        // Validating selected cash payment for authenticated user.
        if ($cashPayment->user_id != Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Deleting seleted cash payment.
        $deleteCashPayment = CashPayment::where('id', $cashPayment->id)->delete();

        // Returning success API response.
        return $this->success(null, 'Cash payment was deleted successfully.');
    }
}
