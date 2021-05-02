<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function coa_details()
    {
        return $this->hasMany(ChartOfAccountDetail::class, 'chart_of_account_id');
    }
}
