<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\Concert;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\LocationSeatRow;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LocationSeat;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Rules\Reservations;

class ShowController extends Controller
{
    public function index() {
        
    }

    public function seating(string $concertId, string $showId) {
        $show = Show::find($showId);
        $concert = Concert::find($concertId);

        if (!$show || !$concert) {
            return response()->json([
                'error' => 'A concert or show with this ID does not exist'
            ], 404);
        }

        $showQuery = Show::
        select(
            '*',
            DB::raw('COUNT(location_seats.id) AS total')
        )
        ->leftJoin(
            'location_seat_rows',
            'location_seat_rows.show_id',
            'shows.id'
        )
        ->leftJoin(
            'location_seats',
            'location_seats.location_seat_row_id',
            'location_seat_rows.id'
        )
        ->where('shows.id', $showId)
        ->where('shows.concert_id', $concertId)
        ->orderBy('location_seat_rows.order', 'asc')
        ->orderBy('location_seats.number', 'asc')
        ->groupBy('location_seat_rows.id')
        ->get();

        return ['rows' => $showQuery->map(function($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'seats' => [
                        'total' => $row->total,
                        'unavailable' => $row->locationSeatRow->unavailable
                    ]
                ];
            })];
    }

    public function reservation(Request $request, $concertid, $showId) {
        $request->validate([
            'reservations' => new Reservations,
            'duration' => 'min:1|max:300'
        ]);
        $concert = Concert::find($concertid);
        $show = Show::find($showId);

        if (!$concert || !$show) {
            return response()->json([
                'error' => 'A concert or show with this ID does not exist'
            ]);
        }

        $reservation_token = $request->reservation_token;
        $reservations = $request->reservations;
        $duration = $request->duration ?? 300;

        $reservationExists = Reservation::where('token', $reservation_token)->first();
        
        if (!$reservationExists && $reservation_token) {
            return response()->json([
                'error' => 'Invalid reservation token'
            ], 403);
        }

        if (!$reservation_token) {
            $reservation_token = Str::random(12);
        }

        // creating reservation
        $newReservation = new Reservation();
        $newReservation->token = $reservation_token;
        $newReservation->expires_at = now()->addSeconds($duration);
        $newReservation->save();

        foreach($reservations as $reservation) {
            LocationSeat::where(
                'location_seat_row_id', 
                $reservation['row']
            )
            ->where('number', $reservation['seat'])
            ->update(['reservation_id' => $newReservation->id]);
        }

        return response()->json([
            'reserved' => true,
            'reservation_token' => $reservation_token,
            'reserved_until' => $duration
        ]);
    }

    public function booking(Request $request, string $concertId, string $showId) {
        $request->validate([
            'reservation_token' => 'required',
            'name' => 'required',
            'city' => 'string|required',
            'zip' => 'string|required',
        ]);

        $reservation_token = $request->reservation_token;
        $name = $request->name;
        $address = $request->address;
        $city = $request->city;
        $zip = $request->zip;
        $country = $request->country;

        $reservation = Reservation::where('token', $reservation_token)->first();

        $concert = Concert::find($concertId);
        $show = Show::find($showId);

        if (!$concert || !$show) {
            return response()->json([
                'error' => 'A concert with this ID does not exist'
            ]);
        }

        $newBooking = new Booking();
        $newBooking->name = $name;
        $newBooking->address = $address;
        $newBooking->city = $city;
        $newBooking->zip = $zip;
        $newBooking->country = $country;
        $newBooking->save();

        $generatedTicketCode = Str::random(10);
        $uppercaseTicketCode = Str::upper($generatedTicketCode);

        $newTicket = new Ticket();
        $newTicket->code = $uppercaseTicketCode;
        $newTicket->booking_id = $newBooking->id;
        $newTicket->created_at = now();
        $newTicket->save();

        LocationSeat::where('reservation_id', $reservation->id)
        ->update(['ticket_id' => $newTicket->id]);

        // Ticket::with(
        //     'ticketBooking', 
        //     'ticketSeats'
        // )
        // ->get()
        // ->map(function($ticket) {
        //     return $ticket;
        //     return [
        //         'id' => $ticket->id,
        //         'code' => $ticket->code,
        //         'name' => $ticket->ticketBooking->name,
        //         'created_at' => $ticket->created_at,
        //     ];
        // })

        return response()->json([
            'tickets' => Show
            ::select(
                'tickets.id AS ticket_id',
                'tickets.code AS code',
                'bookings.name AS name',
                'tickets.created_at AS created_at',
                'row.id AS row_id',
                'row.name AS row_name',
                'seats.number AS number',
                'shows.id AS show_id',
                'shows.start AS start',
                'shows.end AS end',
                'concerts.id AS concert_id',
                'concerts.artist AS concert_artist',
                'locations.id AS location_id',
                'locations.name AS location_name'
            )
            ->leftJoin(
                'location_seat_rows as row',
                'row.show_id',
                '=',
                'shows.id'
            )
            ->leftJoin(
                'location_seats as seats',
                'seats.location_seat_row_id',
                'row.id'
            )
            ->leftJoin(
                'tickets',
                'seats.ticket_id',
                'tickets.id'
            )
            ->leftJoin(
                'bookings',
                'bookings.id',
                'tickets.booking_id'
            )
            ->leftJoin(
                'concerts',
                'concerts.id',
                'shows.concert_id'
            )
            ->leftJoin(
                'locations',
                'locations.id',
                'concerts.location_id'
            )
            ->where('shows.id', $showId)
            ->where('shows.concert_id', $concertId)
            ->where('tickets.id', $newTicket->id)
            ->get()
            ->map(function($ticket) {
                return [
                    'id' => $ticket->ticket_id,
                    'code' => $ticket->code,
                    'name' => $ticket->name,
                    'created_at' => $ticket->created_at,
                    'row' => [
                        'id' => $ticket->row_id,
                        'name' => $ticket->row_name
                    ],
                    'seat' => $ticket->number,
                    'show' => [
                        'id' => $ticket->show_id,
                        'start' => $ticket->start,
                        'end' => $ticket->end,
                        'concert' => [
                            'id' => $ticket->concert_id,
                            'artist' => $ticket->concert_artist,
                            'location' => [
                                'id' => $ticket->location_id,
                                'name' => $ticket->location_name
                            ]
                        ]
                    ]
                ];
            })
        ]);
    }

    public function tickets(Request $request) {
        if (!$request->code  || !$request->name) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ticket = Ticket::where('code', $request->code)->get();

        if (count($ticket) < 1) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $tickets = Show
            ::select(
                'tickets.id AS ticket_id',
                'tickets.code AS code',
                'bookings.name AS name',
                'tickets.created_at AS created_at',
                'row.id AS row_id',
                'row.name AS row_name',
                'seats.number AS number',
                'shows.id AS show_id',
                'shows.start AS start',
                'shows.end AS end',
                'concerts.id AS concert_id',
                'concerts.artist AS concert_artist',
                'locations.id AS location_id',
                'locations.name AS location_name'
            )
            ->leftJoin(
                'location_seat_rows as row',
                'row.show_id',
                '=',
                'shows.id'
            )
            ->leftJoin(
                'location_seats as seats',
                'seats.location_seat_row_id',
                'row.id'
            )
            ->leftJoin(
                'tickets',
                'seats.ticket_id',
                'tickets.id'
            )
            ->leftJoin(
                'bookings',
                'bookings.id',
                'tickets.booking_id'
            )
            ->leftJoin(
                'concerts',
                'concerts.id',
                'shows.concert_id'
            )
            ->leftJoin(
                'locations',
                'locations.id',
                'concerts.location_id'
            )
            ->where('tickets.code', $request->code)
            ->where('bookings.name', $request->name)
            ->get()
            ->map(function($ticket) {
                return [
                    'id' => $ticket->ticket_id,
                    'code' => $ticket->code,
                    'name' => $ticket->name,
                    'created_at' => $ticket->created_at,
                    'row' => [
                        'id' => $ticket->row_id,
                        'name' => $ticket->row_name
                    ],
                    'seat' => $ticket->number,
                    'show' => [
                        'id' => $ticket->show_id,
                        'start' => $ticket->start,
                        'end' => $ticket->end,
                        'concert' => [
                            'id' => $ticket->concert_id,
                            'artist' => $ticket->concert_artist,
                            'location' => [
                                'id' => $ticket->location_id,
                                'name' => $ticket->location_name
                            ]
                        ]
                    ]
                ];
            });

        return $tickets;
    }

    public function cancel(Request $request, $ticketId) {
        if (!$request->code  || !$request->name) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ticket = Ticket::where('id', $ticketId)->first();

        if (!$ticket) {
            return response()->json(['error' => 'A ticket with this ID does not exist'], 404);
        }

        $reservationId = LocationSeat::where('ticket_id', $ticketId)
        ->first()->reservation_id;

        LocationSeat::where('reservation_id', $reservationId)->delete();

        LocationSeat::where('ticket_id', $ticketId)
        ->update(['reservation_id' => null, 'ticket_id' => null]);

        Ticket::where('code', $request->code)->delete();
        return 'cancel';
    }
}
