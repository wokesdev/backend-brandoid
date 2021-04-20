<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
