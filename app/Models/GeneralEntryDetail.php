<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralEntryDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function general_entry()
    {
        return $this->belongsTo(GeneralEntryDetail::class, 'general_entry_id');
    }

    public function coa_detail()
    {
        return $this->belongsTo(ChartOfAccountDetail::class, 'coa_detail_id');
    }
}
