<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Vat;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

     public function test_it_creates_an_order_with_items(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);

        $response = $this->postJson('/api/orders', [
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
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'ok' => 1,
            'message' => 'Order created successfully',
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'payment_id' => $payment->id,
            'number' => 1001,
            'status' => 'created',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_code' => $product->code,
            'quantity' => 2,
            'price' => 10,
            'discount' => 10,
            'total' => 18,
        ]);
    }

    public function test_it_validates_required_fields_when_creating_an_order(): void {
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'customer_id',
            'payment_id',
            'number',
            'items',
        ]);
    }

    public function test_it_returns_paginated_orders(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);
        $order = $this->createOrder($customer, $payment, 1001, 'Test order');

        $order->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200);

        $response->assertJsonPath('data.0.id', $order->id);

        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }

    public function test_it_filters_orders_by_status(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();

        $createdOrder = $this->createOrder( $customer, $payment, 1001, 'Created order');

        $completedOrder = $this->createOrder($customer, $payment, 1002, 'Completed order');

        $completedOrder->update(['status' => 'completed']);

        $response = $this->getJson('/api/orders?status=completed');

        $response->assertStatus(200);

        $response->assertJsonPath('data.0.id', $completedOrder->id);
        $response->assertJsonPath('data.0.status', 'completed');

        $this->assertCount(
            1,
            $response->json('data')
        );
    }

    public function test_it_searches_orders_by_customer_name(): void {
        $vat = $this->createVat();
        $payment = $this->createPayment();

        $customer1 = $this->createCustomer();
        $customer2 = Customer::create([
            'name' => 'John',
            'surname' => 'Doe',
            'phone' => '6911111111',
            'address_name' => 'Second Street',
            'address_number' => '2',
            'zip' => '54321',
            'city' => 'Athens',
            'country' => 'Greece',
            'afm' => 987654321,
        ]);

        $order1 = $this->createOrder(
            $customer1,
            $payment,
            1001,
            'Order 1'
        );

        $order2 = $this->createOrder(
            $customer2,
            $payment,
            1002,
            'Order 2'
        );

        $response = $this->getJson('/api/orders?search=John');

        $response->assertStatus(200);

        $response->assertJsonPath('data.0.id', $order2->id);

        $this->assertCount(
            1,
            $response->json('data')
        );
    }

    public function test_it_searches_orders_by_number(): void {
        $customer = $this->createCustomer();
        $payment = $this->createPayment();

        $order1 = $this->createOrder(
            $customer,
            $payment,
            1001,
            'Order 1'
        );

        $order2 = $this->createOrder(
            $customer,
            $payment,
            2002,
            'Order 2'
        );

        $response = $this->getJson('/api/orders?search=2002');

        $response->assertStatus(200);

        $response->assertJsonPath('data.0.id', $order2->id);

        $this->assertCount(
            1,
            $response->json('data')
        );
    }

    public function test_it_sorts_orders_by_number_descending(): void {
        $customer = $this->createCustomer();
        $payment = $this->createPayment();

        $order1 = $this->createOrder(
            $customer,
            $payment,
            1001,
            'Order 1'
        );

        $order2 = $this->createOrder(
            $customer,
            $payment,
            2002,
            'Order 2'
        );

        $response = $this->getJson('/api/orders?sort=-number');

        $response->assertStatus(200);

        $response->assertJsonPath('data.0.id', $order2->id);
        $response->assertJsonPath('data.1.id', $order1->id);
    }

    public function test_it_returns_a_single_order_with_relations(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);

        $order = $this->createOrder(
            $customer,
            $payment,
            1001,
            'Test order'
        );

        $orderItem = $order->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200);

        $response->assertJsonPath('data.id', $order->id);
        $response->assertJsonPath('data.number', 1001);

        $response->assertJsonPath('data.customer.id', $customer->id);
        $response->assertJsonPath('data.payment.id', $payment->id);
        $response->assertJsonPath('data.order_items.0.id', $orderItem->id);
    }

    public function test_it_updates_an_order(): void {
        $customer = $this->createCustomer();
        $payment = $this->createPayment();

        $order = $this->createOrder(
            $customer,
            $payment,
            1001,
            'Original order'
        );

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => 'completed',
            'notes' => 'Updated order',
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.id', $order->id);
        $response->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'notes' => 'Updated order',
        ]);
    }

    public function test_it_validates_order_update(): void {
        $customer = $this->createCustomer();
        $payment = $this->createPayment();

        $order = $this->createOrder(
            $customer,
            $payment,
            1001,
            'Test order'
        );

        $response = $this->putJson("/api/orders/{$order->id}", [
            'items' => [
                [
                    'quantity' => 0,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'items.0.quantity',
        ]);
    }

    public function test_it_deletes_an_order_and_its_items(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);

        $order = $this->createOrder(
            $customer,
            $payment,
            1001,
            'Test order'
        );

        $orderItem = $order->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'ok' => 1,
            'message' => 'Order deleted successfully',
        ]);

        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);

        $this->assertDatabaseMissing('order_items', [
            'id' => $orderItem->id,
        ]);
    }

    public function test_it_returns_404_for_a_non_existent_order(): void {
        $response = $this->getJson('/api/orders/999999');

        $response->assertStatus(404);
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
