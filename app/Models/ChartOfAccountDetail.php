<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccountDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function coa()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
