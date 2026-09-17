<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderService
{
    public function createOrderWithItems(array $validated): Order {
        return DB::transaction(function () use ($validated) {
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'payment_id' => $validated['payment_id'],
                'number' => $validated['number'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'created',
                'dt' => Carbon::now()->format('Y-m-d'),
                'tm' => Carbon::now()->format('H:i:s'),
            ]);

            foreach($validated['items'] as $item) {
                $product = Product::where('code', $item['product_code'])
                    ->firstOrFail();

                $price = $item['price'] ?? $product->price;
                $discount = $item['discount'] ?? $product->discount;
                $total = OrderItem::calculateTotal($item['quantity'], $price, $discount);

                $order->orderItems()->create([
                    'product_code' => $item['product_code'],
                    'name' => $item['name'] ?? $product->name,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'discount' => $discount,
                    'vat_code' => $product->vat_code,
                    'total' => $total,
                ]);
            }

            return $order;
        });
    }

    public function updateOrder(Order $order, array $validated): Order {
        return DB::transaction(function () use ($order, $validated) {
            // Update Order
            $orderData = collect($validated)
                ->except('items')
                ->toArray();

            $order->update($orderData);

            if(isset($validated['items'])) {
                // Collect all the items id from the request
                $itemIds = collect($validated['items'])
                    ->pluck('id')
                    ->filter();

                // Delete items where there is no id in the request
                $order->orderItems()
                    ->whereNotIn('id', $itemIds)
                    ->delete();

                foreach($validated['items'] as $item) {
                    if(!empty($item['id'])) {
                        // Update existing item
                        $orderItem = $order->orderItems()
                            ->where('id', $item['id'])
                            ->firstOrFail();

                        $quantity = $item['quantity'] ?? $orderItem->quantity;
                        $price    = $item['price'] ?? $orderItem->price;
                        $discount = $item['discount'] ?? $orderItem->discount;
                        $total    = OrderItem::calculateTotal($quantity, $price, $discount);

                        $orderItem->update([
                            'quantity' => $quantity,
                            'discount' => $discount,
                            'price' => $price,
                            'total' => $total,
                        ]);
                    } else {
                        // Create new item
                        $product = Product::where('code', $item['product_code'])
                            ->firstOrFail();

                        $quantity = $item['quantity'];
                        $price    = $item['price'] ?? $product->price;
                        $discount = $item['discount'] ?? $product->discount;
                        $total    = OrderItem::calculateTotal($quantity, $price, $discount);

                        $order->orderItems()->create([
                            'product_code' => $item['product_code'],
                            'name' => $item['name'] ?? $product->name,
                            'price' => $price,
                            'quantity' => $quantity,
                            'discount' => $discount,
                            'vat_code' => $product->vat_code,
                            'total' => $total
                        ]);
                    }
                }
            }

            return $order;
        });
    }
}