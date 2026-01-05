<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Approval;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $manager;
    protected $salesA;
    protected $salesB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users for different roles
        $this->manager = User::factory()->create([
            'role' => 'manager_operasional',
            'email' => 'manager@test.com'
        ]);

        $this->salesA = User::factory()->create([
            'role' => 'sales_field',
            'email' => 'salesA@test.com'
        ]);

        $this->salesB = User::factory()->create([
            'role' => 'sales_field',
            'email' => 'salesB@test.com'
        ]);
    }

    /**
     * TEST CASE 1: Race Condition Logic / Stock Validation
     * Scenario: Product has 5 stock, try to order 6 items
     * Expected: Should fail with error, stock should not decrease
     */
    public function test_race_condition_stock_validation()
    {
        // Arrange: Create product with limited stock
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'stock' => 5,
            'price' => 10000
        ]);

        $customer = Customer::factory()->create([
            'user_id' => $this->salesA->id,
            'name' => 'Test Customer'
        ]);

        // Act: Try to order more than available stock
        $response = $this->actingAs($this->salesA)->post('/orders', [
            'customer_id' => $customer->id,
            'payment_type' => 'cash',
            'product_id' => [$product->id],
            'quantity' => [6], // Request 6 items when only 5 available
            'notes' => 'Test order'
        ]);

        // Assert: Should fail with validation error
        $response->assertSessionHasErrors();

        // Assert: Stock should remain unchanged (no race condition)
        $product->refresh();
        $this->assertEquals(5, $product->stock);

        // Assert: No order should be created
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->salesA->id,
            'customer_id' => $customer->id
        ]);
    }

    /**
     * TEST CASE 2: IDOR Protection (Order & Receivable)
     * Scenario: Sales A tries to access Sales B's order details
     * Expected: Should return 403 Forbidden
     */
    public function test_idor_protection_order_and_receivable()
    {
        // Arrange: Create order for Sales B
        $product = Product::factory()->create(['stock' => 10]);
        $customer = Customer::factory()->create([
            'user_id' => $this->salesB->id
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->salesB->id,
            'customer_id' => $customer->id,
            'status' => 'approved'
        ]);

        // Act: Sales A tries to access Sales B's order detail
        $response = $this->actingAs($this->salesA)->get("/orders/{$order->id}");

        // Assert: Should return 403 Forbidden
        $response->assertForbidden();

        // Also test receivables access
        $response2 = $this->actingAs($this->salesA)->get("/receivables/{$order->id}");
        $response2->assertForbidden();
    }

    /**
     * TEST CASE 3: Malicious File Upload (Mime-Type Check)
     * Scenario: Try to upload PHP file disguised as image
     * Expected: Should fail with validation error
     */
    public function test_malicious_file_upload_mime_type_validation()
    {
        // Arrange: Create fake malicious file
        Storage::fake('public');
        $maliciousFile = UploadedFile::fake()->create('exploit.php', 1024, 'application/x-php');

        // Act: Try to upload via profile photo endpoint
        $response = $this->actingAs($this->salesA)->post('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'photo' => $maliciousFile,
            '_token' => csrf_token()
        ]);

        // Assert: Should fail with validation error
        $response->assertSessionHasErrors('photo');

        // Also test via receivable payment proof
        $order = Order::factory()->create([
            'user_id' => $this->salesA->id,
            'status' => 'approved',
            'total_price' => 50000
        ]);

        $response2 = $this->actingAs($this->salesA)->post("/receivables/{$order->id}/pay", [
            'amount' => 25000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'proof_file' => $maliciousFile,
            '_token' => csrf_token()
        ]);

        $response2->assertSessionHasErrors('proof_file');

        // Also test via visit creation
        $customer = Customer::factory()->create([
            'user_id' => $this->salesA->id,
            'category' => 'Workshop' // Ensure category is set
        ]);

        $response3 = $this->actingAs($this->salesA)->post('/visits', [
            'customer_id' => $customer->id,
            'type' => 'existing',
            'notes' => 'Test visit',
            'latitude' => -6.2088,  // Jakarta coordinates
            'longitude' => 106.8456,
            'photo' => $maliciousFile,
            '_token' => csrf_token()
        ]);

        $response3->assertSessionHasErrors('photo');
    }

    /**
     * TEST CASE 4: Approval Rate Limiting
     * Scenario: Manager tries to approve 11 times rapidly
     * Expected: First 10 succeed, 11th returns 429 Too Many Requests
     */
    public function test_approval_rate_limiting()
    {
        // Arrange: Create approval request
        $order = Order::factory()->create([
            'user_id' => $this->salesA->id,
            'status' => 'pending_approval'
        ]);

        $approval = Approval::factory()->create([
            'model_type' => Order::class,
            'model_id' => $order->id,
            'action' => 'approve_order',
            'status' => 'pending'
        ]);

        // Act & Assert: First 10 requests should succeed
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->actingAs($this->manager)->put("/approvals/{$approval->id}/approve", [
                '_token' => csrf_token()
            ]);

            // Should not be rate limited (status not 429)
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // Act: 11th request should be rate limited
        $response11 = $this->actingAs($this->manager)->put("/approvals/{$approval->id}/approve", [
            '_token' => csrf_token()
        ]);

        // Assert: Should return 429 Too Many Requests
        $response11->assertStatus(429);
    }
}
