<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConcertsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $concerts = Concert::select('*')
        ->orderBy('artist', 'asc')
        ->get()
        ->map(function($concert) {
            return collect($concert)
            ->except(['concert_location', 'concert_shows']);
        });

        return [
            'concerts' => $concerts
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $concert = null;
        try {
            $concert = Concert::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'A concert with this ID does not exist'
            ], 404);
        }

        return collect($concert)->except(['concert_location', 'concert_shows']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
