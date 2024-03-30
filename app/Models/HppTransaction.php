<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HppTransaction extends Model
{
    use HasFactory;

    public function previousTransactions()
    {
        return $this->hasMany(HppTransaction::class, 'id', 'id')->where('id', '<', $this->id);
    }


}
