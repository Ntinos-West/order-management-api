<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Vat;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_item_without_a_discount(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);
        $order = $this->createOrder($customer, $payment, 1001, 'Test order');

        $response = $this->postJson('/api/order-items', [
            'product_code' => $product->code,
            'order_id' => $order->id,
            'vat_code' => $vat->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_code' => $product->code,
            'quantity' => 2,
            'price' => 10,
            'discount' => 0,
            'total' => 20,
        ]);
    }

    public function test_it_validates_required_fields_when_creating_an_order_item(): void {
        $response = $this->postJson('/api/order-items', []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'product_code',
            'order_id',
            'vat_code',
            'name',
            'price',
            'quantity',
        ]);
    }

    public function test_it_keeps_the_total_when_only_the_name_is_updated(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);
        $order = $this->createOrder($customer, $payment, 1001, 'Test order');

        $orderItem = $order->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 10,
            'vat_code' => $vat->code,
            'total' => 18,
        ]);

        $response = $this->putJson("/api/order-items/{$orderItem->id}", [
            'name' => 'Renamed product',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'name' => 'Renamed product',
            'quantity' => 2,
            'price' => 10,
            'discount' => 10,
            'total' => 18,
        ]);
    }

    public function test_it_recalculates_the_total_when_the_quantity_is_updated(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);
        $order = $this->createOrder($customer, $payment, 1001, 'Test order');

        $orderItem = $order->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 10,
            'vat_code' => $vat->code,
            'total' => 18,
        ]);

        $response = $this->putJson("/api/order-items/{$orderItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertStatus(200);

        // 5 x 10 with a 10% discount
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'quantity' => 5,
            'total' => 45,
        ]);
    }

    public function test_it_clears_the_orders_cache_when_an_order_item_changes(): void {
        $vat = $this->createVat();
        $customer = $this->createCustomer();
        $payment = $this->createPayment();
        $product = $this->createProduct($vat);
        $order = $this->createOrder($customer, $payment, 1001, 'Test order');

        $orderItem = $order->orderItems()->create([
            'product_code' => $product->code,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'discount' => 0,
            'vat_code' => $vat->code,
            'total' => 20,
        ]);

        $firstResponse = $this->getJson('/api/orders');

        $this->assertEquals(2, $firstResponse->json('data.0.order_items.0.quantity'));

        $this->putJson("/api/order-items/{$orderItem->id}", [
            'quantity' => 5,
        ])->assertStatus(200);

        $secondResponse = $this->getJson('/api/orders');

        $this->assertEquals(5, $secondResponse->json('data.0.order_items.0.quantity'));
    }

    public function test_it_returns_404_when_updating_a_non_existent_order_item(): void {
        $response = $this->putJson('/api/order-items/999999', [
            'name' => 'Does not exist',
        ]);

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