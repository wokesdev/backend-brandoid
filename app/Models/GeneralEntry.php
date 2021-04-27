<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralEntry extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function general_entry_details()
    {
        return $this->hasMany(GeneralEntryDetail::class, 'general_entry_id');
    }
}
