<?php

namespace App\Rules;

use App\Models\LocationSeat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Reservations implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errors = [];

        foreach ($value AS $reservation) {
            $reservationExist = LocationSeat::where(
                'location_seat_row_id', 
                $reservation['row']
            )
            ->where('number', $reservation['seat'])
            ->first();

            $number = $reservationExist->number;
            $row = $reservationExist->location_seat_row_id;
            $errors[] = "Seat $number in row $row is already taken";
        }

        if (count($errors) > 1) {
            $fail($errors[0]);
        }
    }
}
