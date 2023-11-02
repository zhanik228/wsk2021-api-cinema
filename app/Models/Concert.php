<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    use HasFactory;

    protected $appends = ['location', 'shows'];

    public function getLocationAttribute() {
        return $this->concertLocation;
    }

    public function concertLocation() {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function getShowsAttribute() {
        return $this->concertShows;
    }

    public function concertShows() {
        return $this->hasMany(Show::class)->orderBy('start', 'desc');
    }
}
