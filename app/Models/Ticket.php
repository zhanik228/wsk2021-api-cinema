<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function ticketBooking() {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function ticketSeats() {
        return $this->hasOne(LocationSeat::class);
    }
}
