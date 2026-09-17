<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vat;

class VatController extends Controller
{
    public function index() {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'All vats';

        $vats = Vat::all();

        $ret_array['data'] = $vats;

        return response()->json($ret_array);
    }

    public function show($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Vat with id {'.$id.'} found';

        $vat = Vat::find($id);

        if($vat === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Vat with id {'.$id.'} does not exist';

            return response()->json($ret_array, 404);
        }

        $ret_array['data'] = $vat;

        return response()->json($ret_array);
    }

    public function store(Request $request) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Vat created successfully';

        // Το exists ελέγχει αν υπάρχει το συγκεκριμένο value στον πίνακα
        $validated = $request->validate([
            'code' => 'required|integer',
            'name' => 'required|string',
            'number' => 'required|integer'
        ]);

        $vat_cnt = Vat::where('code', $request->code)->count();

        if($vat_cnt > 0) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Vat already exists';

            return response()->json($ret_array, 409);
        }

        $vat = Vat::create($validated);

        $ret_array['data'] = $vat;

        return response()->json($ret_array, 201);
    }

    public function update(Request $request, $id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Vat updated successfully';

        $vat = Vat::find($id);

        if($vat === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Vat not found';

            return response()->json($ret_array, 404);
        }

        $validated = $request->validate([
            'code' => 'integer',
            'name' => 'string',
            'number' => 'integer'
        ]);

        $vat->update($validated);

        $ret_array['data'] = $vat;

        return response()->json($ret_array);
    }

    public function destroy($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Vat deleted successfully';
        
        $vat = Vat::find($id);

        if($vat === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Vat not found';

            return response()->json($ret_array, 404);
        }

        $vat->delete();

        $ret_array['data'] = $vat;

        return response()->json($ret_array);
    }
}
