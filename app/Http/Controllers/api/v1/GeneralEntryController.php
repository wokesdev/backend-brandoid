<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\GeneralEntry;
use App\Models\GeneralEntryDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GeneralEntryController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all general entries along with general entry's details.
        $generalEntries = GeneralEntry::with('general_entry_details')->where('user_id', Auth::id())->get();;

        // Returning success API response.
        return $this->success($generalEntries, 'All general entries was retrieved successfully.');
    }

    public function store(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'rincian_akun_debit_id' => 'required|numeric|exists:chart_of_account_details,id',
            'rincian_akun_kredit_id' => 'required|numeric|exists:chart_of_account_details,id',
            'nominal' => 'required|numeric',
        ]);

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr) {
            // Creating new general entry.
            $generalEntry = GeneralEntry::create([
                'user_id' => Auth::id(),
                'nomor_transaksi' => '',
                'tanggal' => $attr['tanggal'],
                'keterangan' => $attr['keterangan'],
            ]);

            // Updating transaction's number for the new general entry.
            $updategeneralEntry = GeneralEntry::where('id', $generalEntry->id)->update([
                'nomor_transaksi' => $generalEntry->id,
            ]);

            // Creating new general entry's details for the new general entry.
            $generalEntryDetailDebit = GeneralEntryDetail::create([
                'general_entry_id' => $generalEntry->id,
                'coa_detail_id' => $attr['rincian_akun_debit_id'],
                'debit' => $attr['nominal'],
                'kredit' => 0,
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::create([
                'general_entry_id' => $generalEntry->id,
                'coa_detail_id' => $attr['rincian_akun_kredit_id'],
                'debit' => 0,
                'kredit' => $attr['nominal'],
            ]);

            // Getting and returning the new general entry along with general entry's details.
            $insertedGeneralEntry = GeneralEntry::with('general_entry_details')->where('id', $generalEntry->id)->get();

            return $insertedGeneralEntry;
        });

        // Returning success API response.
        return $this->success($transaction, 'General entry was created successfully.');
    }

    public function show(GeneralEntry $generalEntry)
    {
        // Validating selected general entry for authenticated user.
        if ($generalEntry->user_id != Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Getting selected general entry along with general entry's details.
        $gnrlEntry = GeneralEntry::with('general_entry_details.coa_detail')->findOrFail($generalEntry->id);

        // Returning success API response.
        return $this->success($gnrlEntry, 'General entry with that id was retrieved successfully.');
    }

    public function update(Request $request, GeneralEntry $generalEntry)
    {
        // Validating selected general entry for authenticated user.
        if ($generalEntry->user_id != Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Validating incoming request.
        $attr = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'rincian_akun_debit_id' => 'required|numeric|exists:chart_of_account_details,id',
            'rincian_akun_kredit_id' => 'required|numeric|exists:chart_of_account_details,id',
            'nominal' => 'required|numeric',
        ]);

        // Beginning database transaction.
        $transaction = DB::transaction(function () use ($attr, $generalEntry) {
            // Updating selected general entry.
            $updateGeneralEntry = GeneralEntry::where('id', $generalEntry->id)->update([
                'tanggal' => $attr['tanggal'],
                'keterangan' => $attr['keterangan'],
            ]);

            // Updating general entry's details for selected general entry.
            $generalEntryDetailDebit = GeneralEntryDetail::where('general_entry_id', $generalEntry->id)->where('kredit', 0)->update([
                'coa_detail_id' => $attr['rincian_akun_debit_id'],
                'debit' => $attr['nominal'],
            ]);

            $generalEntryDetailKredit = GeneralEntryDetail::where('general_entry_id', $generalEntry->id)->where('debit', 0)->update([
                'coa_detail_id' => $attr['rincian_akun_kredit_id'],
                'kredit' => $attr['nominal'],
            ]);

            // Getting and returning updated general entry along with general entry's details.
            $updatedGeneralEntry = GeneralEntry::with('general_entry_details')->where('id', $generalEntry->id)->get();

            return $updatedGeneralEntry;
        });

        // Returning success API response.
        return $this->success($transaction, 'General entry was updated successfully.');
    }

    public function destroy(GeneralEntry $generalEntry)
    {
        // Validating selected general entry for authenticated user.
        if ($generalEntry->user_id != Auth::id()) {
            return $this->error('Access was not allowed.', 403);
         }

         // Deleting selected general entry.
         $deleteGeneralEntry = GeneralEntry::where('id', $generalEntry->id)->delete();

         // Returning success API response.
         return $this->success(null, 'General entry was deleted successfully.');
    }

    public function filterDate(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        if ($attr['from_date'] === $attr['to_date']) {
            // Getting all general entries for authenticated user and selected date.
            $generalEntries = GeneralEntry::where('user_id', Auth::id())->whereDate('tanggal', $attr['from_date'])->pluck('id');

            // Getting all general entries' details along with general entry's and chart of account's details.
            $generalEntryDetails = GeneralEntryDetail::with(['general_entry', 'coa_detail'])->whereIn('general_entry_id', $generalEntries)->get();

            // Counting total of debit and credit.
            $sumDebit = GeneralEntryDetail::whereIn('general_entry_id', $generalEntries)->sum('debit');
            $sumKredit = GeneralEntryDetail::whereIn('general_entry_id', $generalEntries)->sum('kredit');

            // Returning success API response.
            return $this->success([
                'general_entry_details' => $generalEntryDetails,
                'sum_debit' => $sumDebit,
                'sum_kredit' => $sumKredit
            ], "All general entry's details with selected date was retrieved successfully.");
        }

        else {
            // Getting all general entries for authenticated user and selected date.
            $generalEntries = GeneralEntry::where('user_id', Auth::id())->whereBetween('tanggal', array($attr['from_date'], $attr['to_date']))->pluck('id');

            // Getting all general entries' details along with general entry's and chart of account's details.
            $generalEntryDetails = GeneralEntryDetail::with(['general_entry', 'coa_detail'])->whereIn('general_entry_id', $generalEntries)->get();

            // Counting total of debit and credit.
            $sumDebit = GeneralEntryDetail::whereIn('general_entry_id', $generalEntries)->sum('debit');
            $sumKredit = GeneralEntryDetail::whereIn('general_entry_id', $generalEntries)->sum('kredit');

            // Returning success API response.
            return $this->success([
                'general_entry_details' => $generalEntryDetails,
                'sum_debit' => $sumDebit,
                'sum_kredit' => $sumKredit
            ], "All general entry's details with selected date was retrieved successfully.");
        }
    }
}
