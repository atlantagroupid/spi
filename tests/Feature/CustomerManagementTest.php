<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $manager;
    protected $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => 'manager_operasional',
            'email' => 'manager@test.com'
        ]);

        $this->sales = User::factory()->create([
            'role' => 'sales_field',
            'email' => 'sales@test.com'
        ]);
    }

    #[Test]
    public function sales_can_create_customer()
    {
        $customerData = [
            'name' => 'John Doe',
            'phone' => '08123456789',
            'address' => 'Jl. Sudirman No. 123',
            'category' => 'Retail',
            'latitude' => '-6.2088',  // String
            'longitude' => '106.8456', // String
            'credit_limit' => 5000000
        ];

        $response = $this->actingAs($this->sales)->post('/customers', $customerData);

        $response->assertRedirect(route('customers.index'));
        // Customer creation doesn't go through approval - directly creates active customer
        $this->assertDatabaseHas('customers', array_merge([
            'name' => $customerData['name'],
            'phone' => $customerData['phone'],
            'address' => $customerData['address'],
            'category' => $customerData['category'],
            'latitude' => $customerData['latitude'],
            'longitude' => $customerData['longitude'],
            'user_id' => $this->sales->id,
            'credit_limit' => 0, // Hardcoded to 0 in controller
            'status' => 'active' // Default status
        ]));
    }

    #[Test]
    public function customer_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->sales)->post('/customers', [
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['name', 'phone', 'address', 'category']);
    }

    #[Test]
    public function sales_can_view_own_customers()
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->sales->id,
            'name' => 'My Customer'
        ]);

        $response = $this->actingAs($this->sales)->get('/customers');

        $response->assertStatus(200);
        $response->assertSee('My Customer');
    }

    #[Test]
    public function sales_cannot_view_other_sales_customers_on_index()
    {
        $otherSales = User::factory()->create(['role' => 'sales_field']);
        $ownCustomer = Customer::factory()->create([
            'user_id' => $this->sales->id,
            'name' => 'My Customer'
        ]);
        $otherCustomer = Customer::factory()->create([
            'user_id' => $otherSales->id,
            'name' => 'Other Customer'
        ]);

        $response = $this->actingAs($this->sales)->get('/customers');

        $response->assertStatus(200);
        $response->assertSee('My Customer');
        $response->assertDontSee('Other Customer');
    }

    #[Test]
    public function manager_can_approve_customer()
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->sales->id,
            'status' => 'pending_approval'
        ]);

        $response = $this->actingAs($this->manager)->patch("/customers/{$customer->id}/approve");

        $response->assertRedirect();
        $customer->refresh();
        $this->assertEquals('active', $customer->status); // Actual status value
    }

    #[Test]
    public function manager_can_reject_customer()
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->sales->id,
            'status' => 'pending_approval'
        ]);

        $response = $this->actingAs($this->manager)->patch("/customers/{$customer->id}/reject");

        $response->assertRedirect();
        $customer->refresh();
        $this->assertEquals('rejected', $customer->status);
    }



    #[Test]
    public function customer_credit_limit_validation()
    {
        $response = $this->actingAs($this->sales)->post('/customers', [
            'name' => 'Test Customer',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'category' => 'Retail',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
            'credit_limit' => -1000 // Invalid negative value
        ]);

        $response->assertSessionHasErrors('credit_limit');
    }

    #[Test]
    public function customer_phone_max_length_validation()
    {
        $response = $this->actingAs($this->sales)->post('/customers', [
            'name' => 'Test Customer',
            'phone' => str_repeat('1', 25), // Too long (max 20)
            'address' => 'Test Address',
            'category' => 'Retail',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
            'credit_limit' => 1000000
        ]);

        $response->assertSessionHasErrors('phone');
    }

    #[Test]
    public function customer_top_list_shows_credit_customers()
    {
        // Create customers - only those with credit_limit > 0 should appear
        Customer::factory()->create(['user_id' => $this->sales->id, 'credit_limit' => 0, 'name' => 'No Credit']);
        Customer::factory()->create(['user_id' => $this->sales->id, 'credit_limit' => 1000000, 'name' => 'Has Credit']);

        $response = $this->actingAs($this->sales)->get('/customers/top-list');

        $response->assertStatus(200);
        $response->assertSee('Has Credit');
        $response->assertDontSee('No Credit');
    }

    #[Test]
    public function customer_soft_delete_functionality()
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->sales->id
        ]);

        // Soft delete
        $response = $this->actingAs($this->manager)->delete("/customers/{$customer->id}");
        $response->assertRedirect();

        // Customer should be soft deleted
        $this->assertSoftDeleted($customer);

        // Should not appear in normal queries
        $response = $this->actingAs($this->sales)->get('/customers');
        $response->assertDontSee($customer->name);
    }
}
