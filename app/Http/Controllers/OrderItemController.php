<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderItem;

class OrderItemController extends Controller
{
    public function index() {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'All order items';

        $order_items = OrderItem::all();

        $ret_array['data'] = $order_items;

        return response()->json($ret_array);
    }

    public function show($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Order Item with id {'.$id.'} found';

        $order_item = OrderItem::find($id);

        if($order_item === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Order Item with id {'.$id.'} does not exist';

            return response()->json($ret_array, 404);
        }

        $ret_array['data'] = $order_item;

        return response()->json($ret_array);
    }

    public function store(Request $request) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Order Item created successfully';

        // Το exists ελέγχει αν υπάρχει το συγκεκριμένο value στον πίνακα
        $validated = $request->validate([
            'product_code' => 'required|string|exists:products,code',
            'order_id' => 'required|integer|exists:orders,id',
            'vat_code' => 'required|integer|exists:vats,code',
            'name' => 'required|string',
            'price' => 'numeric',
            'quantity' => 'numeric',
            'discount' => 'numeric'
        ]);

        $validated['total'] = $request['quantity'] * $request['price'] * ((100 - $request['discount']) / 100);

        $order_item = OrderItem::create($validated);

        $ret_array['data'] = $order_item;

        return response()->json($ret_array, 201);
    }

    public function update(Request $request, $id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Order Item updated successfully';

        $order_item = OrderItem::find($id);

        if($order_item === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Order Item not found';

            return response()->json($ret_array, 404);
        }

        $validated = $request->validate([
            'product_code' => 'string|exists:products,code',
            'order_id' => 'integer|exists:orders,id',
            'vat_code' => 'integer|exists:vats,code',
            'name' => 'string',
            'price' => 'numeric',
            'quantity' => 'numeric',
            'discount' => 'numeric'
        ]);

        $validated['total'] = $request['quantity'] * $request['price'] * ((100 - $request['discount']) / 100);

        $order_item->update($validated);

        $ret_array['data'] = $order_item;

        return response()->json($ret_array);
    }

    public function destroy($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Order Item deleted successfully';
        
        $order_item = OrderItem::find($id);

        if($order_item === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Order Item not found';

            return response()->json($ret_array, 404);
        }

        $order_item->delete();

        $ret_array['data'] = $order_item;

        return response()->json($ret_array);
    }
}
