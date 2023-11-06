<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    use HasFactory;

    public function locationSeatRow() {
        return $this->belongsTo(LocationSeatRow::class, 'show_id');
    }
}
