<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Order;

use App\Http\Resources\OrderResource;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\CreateOrderWithItemsRequest;

use App\Enums\OrderStatus;
use App\Services\OrderService;


class OrderController extends Controller
{
    public function index(Request $request) {
        $request->validate([
            'status' => ['sometimes', 'string', Rule::enum(OrderStatus::class)],
            'sort' => ['sometimes', 'string', Rule::in([ 'number', '-number', 'status', '-status', 'created_at', '-created_at'])],
            'search' => 'sometimes|string|max:100',
        ]);

        $sort = $request->input('sort', 'created_at');

        $direction = 'asc';

        if(str_starts_with($sort, '-')) {
            $direction = 'desc';
            $sort = substr($sort, 1);
        }

        $orders = Order::with([
            'customer',
            'payment',
            'orderItems'
        ])
        ->when($request->search, function ($query) use ($request) {
            $search = $request->search;

            $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                                ->orWhere('surname', 'like', "%{$search}%");
                    });
            });
        })
        ->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->orderBy($sort, $direction)
        ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Order $order) {
        $order->load([
            'customer',
            'payment',
            'orderItems'
        ]);

        return new OrderResource($order);
    }

    public function store(CreateOrderWithItemsRequest $request, OrderService $orderService) {
        $validated = $request->validated();

        $order = $orderService->createOrderWithItems($validated);

        return response()->json([
            'ok' => 1,
            'message' => 'Order created successfully',
            'data' => $order->load('orderItems')
        ], 201);
    }

    public function update(UpdateOrderRequest $request, Order $order, OrderService $orderService) {
        $validated = $request->validated();

        $order = $orderService->updateOrder($order, $validated);

        $order->load([
            'customer',
            'payment',
            'orderItems'
        ]);

        return new OrderResource($order);
    }

    public function destroy(Order $order) {
        $order->delete();

        return response()->json([
            'ok' => 1,
            'message' => 'Order deleted successfully'
        ]);
    }
}
