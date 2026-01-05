<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Approval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $manager;
    protected $sales;
    protected $customer;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->manager = User::factory()->create([
            'role' => 'manager_operasional',
            'email' => 'manager@test.com'
        ]);

        $this->sales = User::factory()->create([
            'role' => 'sales_field',
            'email' => 'sales@test.com'
        ]);

        // Create test data
        $this->customer = Customer::factory()->create([
            'user_id' => $this->sales->id,
            'name' => 'Test Customer'
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'stock' => 100,
            'price' => 50000
        ]);
    }

    #[Test]
    public function sales_can_create_order()
    {
        $response = $this->actingAs($this->sales)->post('/orders', [
            'customer_id' => $this->customer->id,
            'payment_type' => 'cash',
            'product_id' => [$this->product->id],
            'quantity' => [2],
            'notes' => 'Test order creation'
        ]);

        // Order creation redirects to the created order show page
        $order = \App\Models\Order::latest()->first();
        $response->assertRedirect(route('orders.show', $order->id));

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->sales->id,
            'customer_id' => $this->customer->id,
            'payment_type' => 'cash',
            'status' => 'pending_approval'
        ]);

        // Check order items were created
        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);
    }

    #[Test]
    public function order_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->sales)->post('/orders', [
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['customer_id', 'payment_type', 'product_id']);
    }

    #[Test]
    public function order_creation_validates_stock_availability()
    {
        $response = $this->actingAs($this->sales)->post('/orders', [
            'customer_id' => $this->customer->id,
            'payment_type' => 'cash',
            'product_id' => [$this->product->id],
            'quantity' => [150], // More than available stock (100)
            'notes' => 'Test order'
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->sales->id,
            'customer_id' => $this->customer->id
        ]);
    }

    #[Test]
    public function sales_can_view_own_orders()
    {
        $order = Order::factory()->create([
            'user_id' => $this->sales->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending_approval'
        ]);

        $response = $this->actingAs($this->sales)->get('/orders');

        $response->assertStatus(200);
        $response->assertSee($order->invoice_number);
    }

    #[Test]
    public function sales_cannot_view_other_sales_orders()
    {
        $otherSales = User::factory()->create(['role' => 'sales_field']);
        $otherCustomer = Customer::factory()->create(['user_id' => $otherSales->id]);
        $order = Order::factory()->create([
            'user_id' => $otherSales->id,
            'customer_id' => $otherCustomer->id
        ]);

        $response = $this->actingAs($this->sales)->get("/orders/{$order->id}");

        $response->assertForbidden();
    }

    #[Test]
    public function manager_can_approve_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->sales->id,
            'status' => 'pending_approval'
        ]);

        $approval = Approval::factory()->create([
            'model_type' => Order::class,
            'model_id' => $order->id,
            'action' => 'approve_order',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->manager)->put("/approvals/{$approval->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'approver_id' => $this->manager->id
        ]);

        $order->refresh();
        $this->assertEquals('approved', $order->status);
    }

    #[Test]
    public function order_creation_reduces_product_stock()
    {
        $initialStock = $this->product->stock;

        // Create order through the controller (this should reduce stock)
        $response = $this->actingAs($this->sales)->post('/orders', [
            'customer_id' => $this->customer->id,
            'payment_type' => 'cash',
            'product_id' => [$this->product->id],
            'quantity' => [3], // Order 3 items
        ]);

        // Check stock was reduced during creation
        $this->product->refresh();
        $this->assertEquals($initialStock - 3, $this->product->stock);
    }

    #[Test]
    public function order_status_workflow_works()
    {
        // Create order
        $response = $this->actingAs($this->sales)->post('/orders', [
            'customer_id' => $this->customer->id,
            'payment_type' => 'cash',
            'product_id' => [$this->product->id],
            'quantity' => [1],
        ]);

        $order = Order::latest()->first();
        $this->assertEquals('pending_approval', $order->status);

        // Approve order
        $approval = Approval::where('model_type', Order::class)
                           ->where('model_id', $order->id)
                           ->first();

        $this->actingAs($this->manager)->put("/approvals/{$approval->id}/approve");

        $order->refresh();
        $this->assertEquals('approved', $order->status);
    }

    #[Test]
    public function order_export_pdf_works()
    {
        Order::factory()->create([
            'user_id' => $this->sales->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->manager)->get('/orders/export-list-pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function order_print_pdf_requires_authentication()
    {
        $order = Order::factory()->create([
            'user_id' => $this->sales->id,
            'status' => 'approved'
        ]);

        // Test that unauthenticated users cannot access PDF export
        $response = $this->get("/orders/{$order->id}/export-pdf");
        $response->assertRedirect('/login');
    }
}
