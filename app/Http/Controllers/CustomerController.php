<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index() {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'All customers';

        $customers = Customer::all();

        $ret_array['data'] = $customers;
        return response()->json($ret_array);
    }

    public function show($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = '';

        $customer = Customer::find($id);

        if(empty($customer)) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Customer with id {'.$id.'} does not exist';

            return response()->json($ret_array, 404);
        }

        $ret_array['data'] = $customer;
        return response()->json($ret_array);
    }

    public function store(Request $request) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Customer created successfully';

        $validated = $request->validate([
            'name' => 'required|string',
            'surname' => 'required|string',
            'phone' => 'required|string',
            'address_name' => 'required|string',
            'address_number' => 'required|string',
            'zip' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'afm' => 'required|integer'
        ]);

        $customer = Customer::create($validated);

        $ret_array['data'] = $customer;

        return response()->json($ret_array, 201);
    }

    public function update(Request $request, $id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Customer updated successfully';

        $customer = Customer::find($id);

        if($customer === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Customer not found';

            return response()->json($ret_array, 404);
        }

        $validated = $request->validate([
            'name' => 'string',
            'surname' => 'string',
            'phone' => 'string',
            'address_name' => 'string',
            'address_number' => 'string',
            'zip' => 'string',
            'city' => 'string',
            'country' => 'string',
            'afm' => 'integer'
        ]);

        $customer->update($validated);

        $ret_array['data'] = $customer;

        return response()->json($ret_array);
    }

    public function destroy($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Customer deleted successfully';

        $customer = Customer::find($id);

        if($customer === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Customer not found';

            return response()->json($ret_array, 404);
        }

        $customer->delete();

        $ret_array['data'] = $customer;

        return response()->json($ret_array);
    }
}
