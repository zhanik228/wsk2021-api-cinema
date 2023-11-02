<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationSeatRow extends Model
{
    use HasFactory;

    protected $appends = ['unavailable'];

    public function locationSeats() {
        return $this->hasMany(LocationSeat::class);
    }

    public function getUnavailableAttribute() {
        $arr_numbers = [];
        $this->unavailableSeats->filter(function($seat, $index) use(&$arr_numbers) {
            if ($seat->reservation_id || $seat->ticket_id) {
                $arr_numbers[] = $seat->number;
            }
            return false;
        });

        return $arr_numbers;
    }

    public function unavailableSeats() {
        return $this->hasMany(LocationSeat::class);
    }
}
