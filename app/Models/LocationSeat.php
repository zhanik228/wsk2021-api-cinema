<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationSeat extends Model
{
    use HasFactory;

    // protected $appends = ['location_seat_rows'];

    public $timestamps = false;

    // public function getLocationSeatRowsAttribute() {
    //     return $this->locLocationSeatRows;
    // }

    // public function locLocationSeatRows() {
    //     return $this->belongsTo(
    //         LocationSeatRow::class,
    //         'location_seat_row_id'
    //     );
    // }
}
