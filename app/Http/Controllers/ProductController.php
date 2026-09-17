<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index() {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'All products';

        $products = Product::all();

        $ret_array['data'] = $products;

        return response()->json($ret_array);
    }

    public function show($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Product with id {'.$id.'} found';

        $product = Product::find($id);

        if($product === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Product with id {'.$id.'} does not exist';

            return response()->json($ret_array, 404);
        }

        $ret_array['data'] = $product;

        return response()->json($ret_array);
    }

    public function store(Request $request) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Product created successfully';

        // Το exists ελέγχει αν υπάρχει το συγκεκριμένο value στον πίνακα
        $validated = $request->validate([
            'vat_code' => 'required|integer|exists:vats,code',
            'name' => 'required|string',
            'code' => 'required|string',
            'price' => 'required|numeric',
            'discount' => 'required|numeric',
            'not_active' => 'required|boolean'
        ]);

        $product_cnt = Product::where('code', $request->code)->count();

        if($product_cnt > 0) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Product already exists';

            return response()->json($ret_array, 409);
        }

        $product = Product::create($validated);

        $ret_array['data'] = $product;

        return response()->json($ret_array, 201);
    }

    public function update(Request $request, $id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Product updated successfully';

        $product = Product::find($id);

        if($product === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Product not found';

            return response()->json($ret_array, 404);
        }

        $validated = $request->validate([
            'vat_code' => 'integer|exists:vats,code',
            'name' => 'string',
            'price' => 'numeric',
            'discount' => 'numeric',
            'not_active' => 'boolean'
        ]);

        $product->update($validated);

        $ret_array['data'] = $product;

        return response()->json($ret_array);
    }

    public function destroy($id) {
        $ret_array = [];
        $ret_array['ok'] = 1;
        $ret_array['msg'] = 'Product deleted successfully';
        
        $product = Product::find($id);

        if($product === null) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Product not found';

            return response()->json($ret_array, 404);
        }

        if($product->orderItems()->exists()) {
            $ret_array['ok'] = 0;
            $ret_array['msg'] = 'Product can not be deleted because it is used in orders';

            return response()->json($ret_array, 409);
        }

        $product->delete();

        $ret_array['data'] = $product;

        return response()->json($ret_array);
    }
}
