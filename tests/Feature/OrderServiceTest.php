<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Vat;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_order_with_items(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);

        $validated = [
            'customer_id' => $customer->id,
            'payment_id' => $payment->id,
            'number' => 1001,
            'notes' => 'Test order',
            'items' => [
                [
                    'product_code' => $product->code,
                    'quantity' => 2,
                    'discount' => 10,
                ],
            ],
        ];

        $service = new OrderService();

        $order = $service->createOrderWithItems($validated);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'payment_id' => $payment->id,
            'number' => 1001,
            'status' => 'created',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_code' => 'TEST001',
            'quantity' => 2,
            'price' => 10,
            'discount' => 10,
            'total' => 18,
        ]);
    }

    public function test_it_updates_order_and_syncs_items(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();

        $product1 = $this->createProduct($vat, 'TEST001', 'Product 1', 10);
        $product2 = $this->createProduct($vat, 'TEST002', 'Product 2', 20);

        $order = $this->createOrder($customer, $payment);

        $itemToUpdate = $order->orderItems()->create([
            'product_code' => $product1->code,
            'name' => $product1->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $itemToDelete = $order->orderItems()->create([
            'product_code' => $product2->code,
            'name' => $product2->name,
            'price' => 20,
            'quantity' => 1,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $validated = [
            'status' => 'completed',
            'notes' => 'Updated order',
            'items' => [
                [
                    'id' => $itemToUpdate->id,
                    'quantity' => 5,
                    'discount' => 10,
                ],
                [
                    'product_code' => $product2->code,
                    'quantity' => 2,
                ],
            ],
        ];

        $service = new OrderService();

        $service->updateOrder($order, $validated);

        // Existing item was updated
        $this->assertDatabaseHas('order_items', [
            'id' => $itemToUpdate->id,
            'quantity' => 5,
            'discount' => 10,
            'price' => 10,
            'total' => 45,
        ]);

        // New item was created
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_code' => $product2->code,
            'quantity' => 2,
            'price' => 20,
            'discount' => 0,
            'total' => 40,
        ]);

        // Item missing from request was deleted
        $this->assertDatabaseMissing('order_items', [
            'id' => $itemToDelete->id,
        ]);

        // Order itself was updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'notes' => 'Updated order',
        ]);
    }

    public function test_it_rolls_back_update_when_an_order_item_does_not_belong_to_the_order(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);

        $order = $this->createOrder($customer, $payment);

        $orderItem = $order->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $validated = [
            'status' => 'completed',
            'notes' => 'This should not be saved',
            'items' => [
                [
                    'id' => $orderItem->id,
                    'quantity' => 10,
                ],
                [
                    'id' => 999999,
                    'quantity' => 5,
                ],
            ],
        ];

        $service = new OrderService();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            $service->updateOrder($order, $validated);
        } finally {
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => 'created',
                'notes' => 'Original order',
            ]);

            $this->assertDatabaseHas('order_items', [
                'id' => $orderItem->id,
                'quantity' => 2,
                'total' => 20,
            ]);
        }
    }

    public function test_it_cannot_update_an_order_item_from_another_order(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);

        $order1 = $this->createOrder($customer, $payment, 1001, 'Order 1');
        $order2 = $this->createOrder($customer, $payment, 1002, 'Order 2');

        $orderItem = $order2->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $validated = [
            'items' => [
                [
                    'id' => $orderItem->id,
                    'quantity' => 10,
                ],
            ],
        ];

        $service = new OrderService();

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        $service->updateOrder($order1, $validated);
    }

    private function createVat(): Vat {
        return Vat::create([
            'code' => 24,
            'name' => 'VAT 24%',
            'number' => 24,
        ]);
    }

    private function createCustomer(): Customer {
        return Customer::create([
            'name' => 'Test',
            'surname' => 'Customer',
            'phone' => '6900000000',
            'address_name' => 'Test Street',
            'address_number' => '1',
            'zip' => '12345',
            'city' => 'Athens',
            'country' => 'Greece',
            'afm' => 123456789,
        ]);
    }

    private function createPayment(): Payment {
        return Payment::create([
            'name' => 'Cash',
        ]);
    }

    private function createProduct(Vat $vat, string $code = 'TEST001', string $name = 'Test Product', float $price = 10): Product {
        return Product::create([
            'vat_code' => $vat->code,
            'code' => $code,
            'name' => $name,
            'price' => $price,
            'discount' => 0,
            'not_active' => false,
        ]);
    }

    private function createOrder(Customer $customer, Payment $payment, int $number = 1001, string $notes = 'Original order'): Order {
        return Order::create([
            'customer_id' => $customer->id,
            'payment_id' => $payment->id,
            'number' => $number,
            'notes' => $notes,
            'status' => 'created',
            'dt' => now()->format('Y-m-d'),
            'tm' => now()->format('H:i:s'),
        ]);
    }
}