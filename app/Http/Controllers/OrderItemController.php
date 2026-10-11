<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:1',
            'discount' => 'numeric|min:0|max:100'
        ]);

        $validated['discount'] ??= 0;

        $validated['total'] = OrderItem::calculateTotal(
            $validated['quantity'],
            $validated['price'],
            $validated['discount']
        );

        $order_item = OrderItem::create($validated);

        Cache::tags(['orders'])->flush();

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
            'price' => 'numeric|min:0',
            'quantity' => 'numeric|min:1',
            'discount' => 'numeric|min:0|max:100'
        ]);

        $quantity = $validated['quantity'] ?? $order_item->quantity;
        $price    = $validated['price'] ?? $order_item->price;
        $discount = $validated['discount'] ?? $order_item->discount ?? 0;

        $validated['total'] = OrderItem::calculateTotal($quantity, $price, $discount);

        $order_item->update($validated);

        Cache::tags(['orders'])->flush();

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

        Cache::tags(['orders'])->flush();

        $ret_array['data'] = $order_item;

        return response()->json($ret_array);
    }
}
