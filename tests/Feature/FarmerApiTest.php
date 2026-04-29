<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\Transaction;
use App\Models\Debt;
use App\Models\Repayment;
use Tests\TestCase;

class FarmerApiTest extends TestCase
{
    public function test_index_farmers_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        Farmer::factory()->count(3)->create();

        $response = $this->getJson('/api/farmers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'card_identifier',
                    'firstname',
                    'lastname',
                    'phone',
                    'village',
                    'region',
                    'credit_limit_fcfa',
                    'total_outstanding_debt',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonCount(3);
    }

    public function test_index_farmers_without_authentication(): void
    {
        $response = $this->getJson('/api/farmers');

        $response->assertStatus(401);
    }

    public function test_store_farmer_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $farmerData = [
            'card_identifier' => 'FARMER001',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'phone' => '+226123456789',
            'village' => 'Ouagadougou',
            'region' => 'Centre',
            'credit_limit_fcfa' => 100000.50,
            'total_outstanding_debt' => 0.00,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/farmers', $farmerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'card_identifier',
                'firstname',
                'lastname',
                'phone',
                'village',
                'region',
                'credit_limit_fcfa',
                'total_outstanding_debt',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->assertJson($farmerData);

        $this->assertDatabaseHas('farmers', $farmerData);
    }

    public function test_store_farmer_without_authentication(): void
    {
        $farmerData = [
            'card_identifier' => 'FARMER001',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];

        $response = $this->postJson('/api/farmers', $farmerData);

        $response->assertStatus(401);
    }

    public function test_store_farmer_with_validation_errors(): void
    {
        $this->authenticateOperator();

        $response = $this->postJson('/api/farmers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'card_identifier',
                'firstname',
                'lastname',
            ]);
    }

    public function test_store_farmer_with_duplicate_card_identifier(): void
    {
        $this->authenticateOperator();

        $existingFarmer = Farmer::factory()->create(['card_identifier' => 'DUPLICATE001']);

        $farmerData = [
            'card_identifier' => 'DUPLICATE001',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];

        $response = $this->postJson('/api/farmers', $farmerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['card_identifier']);
    }

    public function test_show_farmer_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/farmers/{$farmer->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'card_identifier',
                'firstname',
                'lastname',
                'phone',
                'village',
                'region',
                'credit_limit_fcfa',
                'total_outstanding_debt',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $farmer->id,
                'card_identifier' => $farmer->card_identifier,
                'firstname' => $farmer->firstname,
                'lastname' => $farmer->lastname,
            ]);
    }

    public function test_show_farmer_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/farmers/{$farmer->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_farmer(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/farmers/999999');

        $response->assertStatus(404);
    }

    public function test_update_farmer_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create();

        $updateData = [
            'firstname' => 'Updated Name',
            'phone' => '+226987654321',
            'village' => 'New Village',
        ];

        $response = $this->putJson("/api/farmers/{$farmer->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('farmers', array_merge(['id' => $farmer->id], $updateData));
    }

    public function test_update_farmer_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->putJson("/api/farmers/{$farmer->id}", [
            'firstname' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_nonexistent_farmer(): void
    {
        $this->authenticateOperator();

        $response = $this->putJson('/api/farmers/999999', [
            'firstname' => 'Updated Name',
        ]);

        $response->assertStatus(404);
    }

    public function test_delete_farmer_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create();

        $response = $this->deleteJson("/api/farmers/{$farmer->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('farmers', ['id' => $farmer->id]);
    }

    public function test_delete_farmer_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->deleteJson("/api/farmers/{$farmer->id}");

        $response->assertStatus(401);
    }

    public function test_delete_nonexistent_farmer(): void
    {
        $this->authenticateOperator();

        $response = $this->deleteJson('/api/farmers/999999');

        $response->assertStatus(404);
    }

    public function test_search_farmers_by_name(): void
    {
        $this->authenticateOperator();

        Farmer::factory()->create(['firstname' => 'John', 'lastname' => 'Doe']);
        Farmer::factory()->create(['firstname' => 'Jane', 'lastname' => 'Smith']);
        Farmer::factory()->create(['firstname' => 'Bob', 'lastname' => 'Johnson']);

        $response = $this->getJson('/api/farmers/search?q=John');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.firstname', 'John');
    }

    public function test_search_farmers_by_village(): void
    {
        $this->authenticateOperator();

        Farmer::factory()->create(['village' => 'Ouagadougou']);
        Farmer::factory()->create(['village' => 'Bobo-Dioulasso']);
        Farmer::factory()->create(['village' => 'Koudougou']);

        $response = $this->getJson('/api/farmers/search?q=Ouaga');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.village', 'Ouagadougou');
    }

    public function test_search_farmers_without_authentication(): void
    {
        $response = $this->getJson('/api/farmers/search?q=test');

        $response->assertStatus(401);
    }

    public function test_get_active_farmers(): void
    {
        $this->authenticateOperator();

        Farmer::factory()->create(['is_active' => true]);
        Farmer::factory()->create(['is_active' => true]);
        Farmer::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/farmers/active');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $farmer) {
            $this->assertTrue($farmer['is_active']);
        }
    }

    public function test_get_active_farmers_without_authentication(): void
    {
        $response = $this->getJson('/api/farmers/active');

        $response->assertStatus(401);
    }

    public function test_get_farmer_transactions(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create();
        $transaction1 = Transaction::factory()->create(['farmer_id' => $farmer->id]);
        $transaction2 = Transaction::factory()->create(['farmer_id' => $farmer->id]);
        Transaction::factory()->create(); // Transaction for different farmer

        $response = $this->getJson("/api/farmers/{$farmer->id}/transactions");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'reference',
                    'farmer_id',
                    'operator_id',
                    'subtotal_fcfa',
                    'payment_method',
                    'interest_rate',
                    'interest_amount_fcfa',
                    'total_fcfa',
                    'status',
                    'notes',
                    'transacted_at',
                ],
            ]);
    }

    public function test_get_farmer_transactions_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/farmers/{$farmer->id}/transactions");

        $response->assertStatus(401);
    }

    public function test_get_farmer_debts(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create();
        $debt1 = Debt::factory()->create(['farmer_id' => $farmer->id]);
        $debt2 = Debt::factory()->create(['farmer_id' => $farmer->id]);
        Debt::factory()->create(); // Debt for different farmer

        $response = $this->getJson("/api/farmers/{$farmer->id}/debts");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'farmer_id',
                    'transaction_id',
                    'original_amount_fcfa',
                    'paid_amount_fcfa',
                    'remaining_amount_fcfa',
                    'status',
                    'incurred_at',
                    'fully_paid_at',
                ],
            ]);
    }

    public function test_get_farmer_debts_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/farmers/{$farmer->id}/debts");

        $response->assertStatus(401);
    }

    public function test_get_farmer_repayments(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create();
        $repayment1 = Repayment::factory()->create(['farmer_id' => $farmer->id]);
        $repayment2 = Repayment::factory()->create(['farmer_id' => $farmer->id]);
        Repayment::factory()->create(); // Repayment for different farmer

        $response = $this->getJson("/api/farmers/{$farmer->id}/repayments");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'reference',
                    'farmer_id',
                    'operator_id',
                    'commodity_kg',
                    'commodity_rate_fcfa_per_kg',
                    'total_fcfa_value',
                    'notes',
                    'repaid_at',
                ],
            ]);
    }

    public function test_get_farmer_repayments_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/farmers/{$farmer->id}/repayments");

        $response->assertStatus(401);
    }

    public function test_get_farmer_financial_summary(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create([
            'credit_limit_fcfa' => 100000.00,
            'total_outstanding_debt' => 25000.00,
        ]);

        $response = $this->getJson("/api/farmers/{$farmer->id}/financial-summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'farmer_id',
                'credit_limit_fcfa',
                'total_outstanding_debt',
                'available_credit',
                'total_transactions_count',
                'total_transactions_amount',
                'total_debts_count',
                'total_debts_amount',
                'total_repayments_count',
                'total_repayments_amount',
            ])
            ->assertJson([
                'farmer_id' => $farmer->id,
                'credit_limit_fcfa' => 100000.00,
                'total_outstanding_debt' => 25000.00,
                'available_credit' => 75000.00,
            ]);
    }

    public function test_get_farmer_financial_summary_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/farmers/{$farmer->id}/financial-summary");

        $response->assertStatus(401);
    }

    public function test_get_farmer_debt_summary(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create();

        Debt::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => 'OUTSTANDING',
            'remaining_amount_fcfa' => 15000.00,
        ]);
        Debt::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => 'OVERDUE',
            'remaining_amount_fcfa' => 10000.00,
        ]);
        Debt::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => 'PAID',
            'remaining_amount_fcfa' => 0.00,
        ]);

        $response = $this->getJson("/api/farmers/{$farmer->id}/debt-summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'farmer_id',
                'total_debts',
                'outstanding_debts',
                'overdue_debts',
                'paid_debts',
                'total_remaining_amount',
                'total_original_amount',
                'total_paid_amount',
            ])
            ->assertJson([
                'farmer_id' => $farmer->id,
                'total_debts' => 3,
                'outstanding_debts' => 1,
                'overdue_debts' => 1,
                'paid_debts' => 1,
                'total_remaining_amount' => 25000.00,
            ]);
    }

    public function test_get_farmer_debt_summary_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/farmers/{$farmer->id}/debt-summary");

        $response->assertStatus(401);
    }

    public function test_update_farmer_credit_limit(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create(['credit_limit_fcfa' => 50000.00]);

        $response = $this->postJson("/api/farmers/{$farmer->id}/update-credit-limit", [
            'credit_limit_fcfa' => 75000.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Credit limit updated successfully',
                'credit_limit_fcfa' => 75000.00,
            ]);

        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'credit_limit_fcfa' => 75000.00,
        ]);
    }

    public function test_update_farmer_credit_limit_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->postJson("/api/farmers/{$farmer->id}/update-credit-limit", [
            'credit_limit_fcfa' => 75000.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_update_farmer_outstanding_debt(): void
    {
        $this->authenticateOperator();

        $farmer = Farmer::factory()->create(['total_outstanding_debt' => 10000.00]);

        $response = $this->postJson("/api/farmers/{$farmer->id}/update-outstanding-debt", [
            'total_outstanding_debt' => 15000.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Outstanding debt updated successfully',
                'total_outstanding_debt' => 15000.00,
            ]);

        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'total_outstanding_debt' => 15000.00,
        ]);
    }

    public function test_update_farmer_outstanding_debt_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->postJson("/api/farmers/{$farmer->id}/update-outstanding-debt", [
            'total_outstanding_debt' => 15000.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_farmer_operations_with_different_roles(): void
    {
        $farmer = Farmer::factory()->create();

        // Test with Admin role
        $admin = $this->authenticateAdmin();
        $response = $this->getJson('/api/farmers');
        $response->assertStatus(200);

        // Test with Supervisor role
        $supervisor = $this->authenticateSupervisor();
        $response = $this->getJson('/api/farmers');
        $response->assertStatus(200);

        // Test with Operator role
        $operator = $this->authenticateOperator();
        $response = $this->getJson('/api/farmers');
        $response->assertStatus(200);
    }
}
