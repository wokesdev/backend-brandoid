<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function coa_detail()
    {
        return $this->belongsTo(ChartOfAccountDetail::class, 'coa_detail_id');
    }

    public function sale_details()
    {
        return $this->hasMany(SaleDetail::class, 'sale_id');
    }

    public function general_entry()
    {
        return $this->hasOne(GeneralEntry::class, 'purchase_id');
    }
}
