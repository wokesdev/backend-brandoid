<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountDetail;
use App\Models\GeneralEntry;
use App\Models\GeneralEntryDetail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncomeStatementController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

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

            // Getting chart of account for sales, other incomes, operational costs, and other costs.
            $whereIsPenjualan = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Penjualan')->first();
            $whereIsPendapatanLain = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Pendapatan Lain-Lain')->first();
            $whereIsBiayaOperasional = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Biaya Operasional')->first();
            $whereIsBiayaLain = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Biaya Lain-Lain')->first();

            // Getting chart of account's details for sales, other incomes, operational costs, and other costs.
            $whereIsPenjualanDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsPenjualan->id)->pluck('id')->toArray();
            $whereIsPendapatanLainDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsPendapatanLain->id)->pluck('id')->toArray();
            $whereIsBiayaOperasionalDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsBiayaOperasional->id)->pluck('id')->toArray();
            $whereIsBiayaLainDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsBiayaLain->id)->pluck('id')->toArray();

            // Getting general entries' id for authenticated user.
            $authUserGeneralEntry = GeneralEntry::where('user_id', Auth::id())->pluck('id')->toArray();

            // Getting general entry's details for sales, other incomes, operational costs, and other costs.
            $penjualanGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPenjualanDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();
            $pendapatanLainGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPendapatanLainDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();
            $biayaOperasionalGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaOperasionalDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();
            $biayaLainGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaLainDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();

            // Summing credit and debit for sales, other incomes, operational costs, and other costs.
            $sumPenjualan = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPenjualanDetail)->whereIn('general_entry_id', $generalEntries)->sum('kredit');
            $sumPendapatanLain = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPendapatanLainDetail)->whereIn('general_entry_id', $generalEntries)->sum('kredit');
            $sumBiayaOperasional = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaOperasionalDetail)->whereIn('general_entry_id', $generalEntries)->sum('debit');
            $sumBiayaLain = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaLainDetail)->whereIn('general_entry_id', $generalEntries)->sum('debit');

            // Counting the net profit.
            $labaBersih = ($sumPenjualan + $sumPendapatanLain) - ($sumBiayaOperasional + $sumBiayaLain);

            // Changing the format of the net profit.
            if ($labaBersih < 0) {
                $labaBersih = '(Rp' . number_format(-($labaBersih), 0, '', '.') . ',-)';
            } else {
                $labaBersih = 'Rp' . number_format($labaBersih, 0, '', '.') . ',-';
            }
        }

        else {
            // Getting all general entries for authenticated user and selected date.
            $generalEntries = GeneralEntry::where('user_id', Auth::id())->whereBetween('tanggal', array($attr['from_date'], $attr['to_date']))->pluck('id');

            // Getting chart of account for sales, other incomes, operational costs, and other costs.
            $whereIsPenjualan = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Penjualan')->first();
            $whereIsPendapatanLain = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Pendapatan Lain-Lain')->first();
            $whereIsBiayaOperasional = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Biaya Operasional')->first();
            $whereIsBiayaLain = ChartOfAccount::select('id', 'nomor_akun', 'nama_akun')->where('nama_akun', 'Biaya Lain-Lain')->first();

            // Getting chart of account's details for sales, other incomes, operational costs, and other costs.
            $whereIsPenjualanDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsPenjualan->id)->pluck('id')->toArray();
            $whereIsPendapatanLainDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsPendapatanLain->id)->pluck('id')->toArray();
            $whereIsBiayaOperasionalDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsBiayaOperasional->id)->pluck('id')->toArray();
            $whereIsBiayaLainDetail = ChartOfAccountDetail::where('chart_of_account_id', $whereIsBiayaLain->id)->pluck('id')->toArray();

            // Getting general entries' id for authenticated user.
            $authUserGeneralEntry = GeneralEntry::where('user_id', Auth::id())->pluck('id')->toArray();

            // Getting general entry's details for sales, other incomes, operational costs, and other costs.
            $penjualanGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPenjualanDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();
            $pendapatanLainGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPendapatanLainDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();
            $biayaOperasionalGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaOperasionalDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();
            $biayaLainGeneralEntryDetails = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaLainDetail)->whereIn('general_entry_id', $generalEntries)->orderBy('coa_detail_id')->groupBy('coa_detail_id')->get();

            // Summing credit and debit for sales, other incomes, operational costs, and other costs.
            $sumPenjualan = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPenjualanDetail)->whereIn('general_entry_id', $generalEntries)->sum('kredit');
            $sumPendapatanLain = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsPendapatanLainDetail)->whereIn('general_entry_id', $generalEntries)->sum('kredit');
            $sumBiayaOperasional = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaOperasionalDetail)->whereIn('general_entry_id', $generalEntries)->sum('debit');
            $sumBiayaLain = GeneralEntryDetail::whereIn('general_entry_id', $authUserGeneralEntry)->whereIn('coa_detail_id', $whereIsBiayaLainDetail)->whereIn('general_entry_id', $generalEntries)->sum('debit');

            // Counting the net profit.
            $labaBersih = ($sumPenjualan + $sumPendapatanLain) - ($sumBiayaOperasional + $sumBiayaLain);

            // Changing the format of the net profit.
            if ($labaBersih < 0) {
                $labaBersih = '(Rp' . number_format(-($labaBersih), 0, '', '.') . ',-)';
            } else {
                $labaBersih = 'Rp' . number_format($labaBersih, 0, '', '.') . ',-';
            }
        }

        // Returning success API response.
        return $this->success([
            'penjualan_coa' => $whereIsPenjualan,
            'pendapatan_lain_coa' => $whereIsPendapatanLain,
            'biaya_operasional_coa' => $whereIsBiayaOperasional,
            'biaya_lain_coa' => $whereIsBiayaLain,
            'penjualan_ge_details' => $penjualanGeneralEntryDetails,
            'pendapatan_lain_ge_details' => $pendapatanLainGeneralEntryDetails,
            'biaya_operasional_ge_details' => $biayaOperasionalGeneralEntryDetails,
            'biaya_lain_ge_details' => $biayaLainGeneralEntryDetails,
            'penjualan_sum' => $sumPenjualan,
            'pendapatan_lain_sum' => $sumPendapatanLain,
            'biaya_operasional_sum' => $sumBiayaOperasional,
            'biaya_lain_sum' => $sumBiayaLain,
            'laba_bersih' => $labaBersih
        ], 'All income statements was retrieved successfully.');
    }
}
