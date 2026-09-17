<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index() {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'All payments';

        $payments = Payment::all();

        $ret_array['data'] = $payments;
        return response()->json($ret_array);
    }

    public function show($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = '';

        $payment = Payment::find($id);

        if($payment === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Payment with id {'.$id.'} does not exist';

            return response()->json($ret_array, 404);
        }

        $ret_array['data'] = $payment;
        return response()->json($ret_array);
    }

    public function store(Request $request) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Payment created successfully';

        $validated = $request->validate([
            'name' => 'required|string'
        ]);

        $payment = Payment::create($validated);

        $ret_array['data'] = $payment;

        return response()->json($ret_array, 201);
    }

    public function update(Request $request, $id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Payment updated successfully';

        $payment = Payment::find($id);

        if($payment === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Payment not found';

            return response()->json($ret_array, 404);
        }

        $validated = $request->validate([
            'name' => 'string'
        ]);

        $payment->update($validated);

        $ret_array['data'] = $payment;

        return response()->json($ret_array);
    }

    public function destroy($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Payment deleted successfully';

        $payment = Payment::find($id);

        if($payment === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Payment not found';

            return response()->json($ret_array, 404);
        }

        $payment->delete();

        $ret_array['data'] = $payment;

        return response()->json($ret_array);
    }
}
