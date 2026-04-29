<?php

namespace Tests\Feature;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Transaction;
use App\Models\Repayment;
use Tests\TestCase;

class DebtApiTest extends TestCase
{
    public function test_index_debts_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        Debt::factory()->count(3)->create();

        $response = $this->getJson('/api/debts');

        $response->assertStatus(200)
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
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonCount(3);
    }

    public function test_index_debts_without_authentication(): void
    {
        $response = $this->getJson('/api/debts');

        $response->assertStatus(401);
    }

    public function test_store_debt_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $farmer = Farmer::factory()->create();
        $transaction = Transaction::factory()->create();

        $debtData = [
            'farmer_id' => $farmer->id,
            'transaction_id' => $transaction->id,
            'original_amount_fcfa' => 10000.00,
            'paid_amount_fcfa' => 0.00,
            'remaining_amount_fcfa' => 10000.00,
            'status' => 'OUTSTANDING',
            'incurred_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/debts', $debtData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'farmer_id',
                'transaction_id',
                'original_amount_fcfa',
                'paid_amount_fcfa',
                'remaining_amount_fcfa',
                'status',
                'incurred_at',
                'fully_paid_at',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('debts', [
            'farmer_id' => $farmer->id,
            'transaction_id' => $transaction->id,
            'original_amount_fcfa' => 10000.00,
            'status' => 'OUTSTANDING',
        ]);
    }

    public function test_store_debt_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();
        $transaction = Transaction::factory()->create();

        $debtData = [
            'farmer_id' => $farmer->id,
            'transaction_id' => $transaction->id,
            'original_amount_fcfa' => 10000.00,
        ];

        $response = $this->postJson('/api/debts', $debtData);

        $response->assertStatus(401);
    }

    public function test_store_debt_with_validation_errors(): void
    {
        $this->authenticateOperator();

        $response = $this->postJson('/api/debts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'farmer_id',
                'transaction_id',
                'original_amount_fcfa',
                'status',
            ]);
    }

    public function test_show_debt_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $debt = Debt::factory()->create();

        $response = $this->getJson("/api/debts/{$debt->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'farmer_id',
                'transaction_id',
                'original_amount_fcfa',
                'paid_amount_fcfa',
                'remaining_amount_fcfa',
                'status',
                'incurred_at',
                'fully_paid_at',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $debt->id,
                'farmer_id' => $debt->farmer_id,
                'transaction_id' => $debt->transaction_id,
            ]);
    }

    public function test_show_debt_without_authentication(): void
    {
        $debt = Debt::factory()->create();

        $response = $this->getJson("/api/debts/{$debt->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_debt(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/debts/999999');

        $response->assertStatus(404);
    }

    public function test_update_debt_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $debt = Debt::factory()->create();

        $updateData = [
            'status' => 'OVERDUE',
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/debts/{$debt->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('debts', array_merge(['id' => $debt->id], $updateData));
    }

    public function test_update_debt_without_authentication(): void
    {
        $debt = Debt::factory()->create();

        $response = $this->putJson("/api/debts/{$debt->id}", [
            'status' => 'OVERDUE',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_debt_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $debt = Debt::factory()->create();

        $response = $this->deleteJson("/api/debts/{$debt->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('debts', ['id' => $debt->id]);
    }

    public function test_delete_debt_without_authentication(): void
    {
        $debt = Debt::factory()->create();

        $response = $this->deleteJson("/api/debts/{$debt->id}");

        $response->assertStatus(401);
    }

    public function test_get_debts_by_farmer(): void
    {
        $this->authenticateOperator();
        
        $farmer = Farmer::factory()->create();
        $debt1 = Debt::factory()->create(['farmer_id' => $farmer->id]);
        $debt2 = Debt::factory()->create(['farmer_id' => $farmer->id]);
        Debt::factory()->create(); // Debt for different farmer

        $response = $this->getJson("/api/debts/farmer/{$farmer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'farmer_id',
                    'transaction_id',
                    'original_amount_fcfa',
                    'remaining_amount_fcfa',
                    'status',
                    'incurred_at',
                ],
            ]);
    }

    public function test_get_debts_by_farmer_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/debts/farmer/{$farmer->id}");

        $response->assertStatus(401);
    }

    public function test_get_debts_by_transaction(): void
    {
        $this->authenticateOperator();
        
        $transaction = Transaction::factory()->create();
        $debt1 = Debt::factory()->create(['transaction_id' => $transaction->id]);
        Debt::factory()->create(); // Debt for different transaction

        $response = $this->getJson("/api/debts/transaction/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.transaction_id', $transaction->id);
    }

    public function test_get_debts_by_transaction_without_authentication(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->getJson("/api/debts/transaction/{$transaction->id}");

        $response->assertStatus(401);
    }

    public function test_get_outstanding_debts(): void
    {
        $this->authenticateOperator();
        
        Debt::factory()->create(['status' => 'OUTSTANDING', 'remaining_amount_fcfa' => 5000.00]);
        Debt::factory()->create(['status' => 'OUTSTANDING', 'remaining_amount_fcfa' => 7500.00]);
        Debt::factory()->create(['status' => 'PAID', 'remaining_amount_fcfa' => 0.00]);

        $response = $this->getJson('/api/debts/outstanding');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $debt) {
            $this->assertEquals('OUTSTANDING', $debt['status']);
            $this->assertGreaterThan(0, $debt['remaining_amount_fcfa']);
        }
    }

    public function test_get_outstanding_debts_without_authentication(): void
    {
        $response = $this->getJson('/api/debts/outstanding');

        $response->assertStatus(401);
    }

    public function test_get_overdue_debts(): void
    {
        $this->authenticateOperator();
        
        Debt::factory()->create(['status' => 'OVERDUE', 'remaining_amount_fcfa' => 3000.00]);
        Debt::factory()->create(['status' => 'OVERDUE', 'remaining_amount_fcfa' => 4500.00]);
        Debt::factory()->create(['status' => 'OUTSTANDING', 'remaining_amount_fcfa' => 2000.00]);

        $response = $this->getJson('/api/debts/overdue');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $debt) {
            $this->assertEquals('OVERDUE', $debt['status']);
            $this->assertGreaterThan(0, $debt['remaining_amount_fcfa']);
        }
    }

    public function test_get_overdue_debts_without_authentication(): void
    {
        $response = $this->getJson('/api/debts/overdue');

        $response->assertStatus(401);
    }

    public function test_get_paid_debts(): void
    {
        $this->authenticateOperator();
        
        Debt::factory()->create(['status' => 'PAID', 'remaining_amount_fcfa' => 0.00]);
        Debt::factory()->create(['status' => 'PAID', 'remaining_amount_fcfa' => 0.00]);
        Debt::factory()->create(['status' => 'OUTSTANDING', 'remaining_amount_fcfa' => 5000.00]);

        $response = $this->getJson('/api/debts/paid');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $debt) {
            $this->assertEquals('PAID', $debt['status']);
            $this->assertEquals(0, $debt['remaining_amount_fcfa']);
        }
    }

    public function test_get_paid_debts_without_authentication(): void
    {
        $response = $this->getJson('/api/debts/paid');

        $response->assertStatus(401);
    }

    public function test_add_payment_to_debt(): void
    {
        $this->authenticateOperator();
        
        $debt = Debt::factory()->create([
            'original_amount_fcfa' => 10000.00,
            'paid_amount_fcfa' => 2000.00,
            'remaining_amount_fcfa' => 8000.00,
        ]);

        $paymentData = [
            'amount_fcfa' => 3000.00,
            'payment_date' => now()->toISOString(),
            'notes' => 'Partial payment',
        ];

        $response = $this->postJson("/api/debts/{$debt->id}/payment", $paymentData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'debt' => [
                    'id',
                    'paid_amount_fcfa',
                    'remaining_amount_fcfa',
                    'status',
                ],
            ])
            ->assertJson([
                'message' => 'Payment added successfully',
                'debt.paid_amount_fcfa' => 5000.00,
                'debt.remaining_amount_fcfa' => 5000.00,
            ]);

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'paid_amount_fcfa' => 5000.00,
            'remaining_amount_fcfa' => 5000.00,
        ]);
    }

    public function test_add_payment_to_debt_without_authentication(): void
    {
        $debt = Debt::factory()->create();

        $response = $this->postJson("/api/debts/{$debt->id}/payment", [
            'amount_fcfa' => 1000.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_add_full_payment_to_debt(): void
    {
        $this->authenticateOperator();
        
        $debt = Debt::factory()->create([
            'original_amount_fcfa' => 10000.00,
            'paid_amount_fcfa' => 2000.00,
            'remaining_amount_fcfa' => 8000.00,
        ]);

        $paymentData = [
            'amount_fcfa' => 8000.00,
            'payment_date' => now()->toISOString(),
        ];

        $response = $this->postJson("/api/debts/{$debt->id}/payment", $paymentData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payment added successfully',
                'debt.status' => 'PAID',
                'debt.paid_amount_fcfa' => 10000.00,
                'debt.remaining_amount_fcfa' => 0.00,
            ]);

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => 'PAID',
            'paid_amount_fcfa' => 10000.00,
            'remaining_amount_fcfa' => 0.00,
        ]);
    }

    public function test_update_remaining_amount(): void
    {
        $this->authenticateOperator();
        
        $debt = Debt::factory()->create(['remaining_amount_fcfa' => 8000.00]);

        $response = $this->putJson("/api/debts/{$debt->id}/update-remaining", [
            'remaining_amount_fcfa' => 6000.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Remaining amount updated successfully',
                'remaining_amount_fcfa' => 6000.00,
            ]);

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'remaining_amount_fcfa' => 6000.00,
        ]);
    }

    public function test_update_remaining_amount_without_authentication(): void
    {
        $debt = Debt::factory()->create();

        $response = $this->putJson("/api/debts/{$debt->id}/update-remaining", [
            'remaining_amount_fcfa' => 6000.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_mark_debt_as_fully_paid(): void
    {
        $this->authenticateOperator();
        
        $debt = Debt::factory()->create([
            'status' => 'OUTSTANDING',
            'remaining_amount_fcfa' => 5000.00,
        ]);

        $response = $this->putJson("/api/debts/{$debt->id}/mark-fully-paid");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Debt marked as fully paid',
                'status' => 'PAID',
                'remaining_amount_fcfa' => 0.00,
            ]);

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => 'PAID',
            'remaining_amount_fcfa' => 0.00,
        ]);
    }

    public function test_mark_debt_as_fully_paid_without_authentication(): void
    {
        $debt = Debt::factory()->create();

        $response = $this->putJson("/api/debts/{$debt->id}/mark-fully-paid");

        $response->assertStatus(401);
    }

    public function test_get_farmer_debt_summary(): void
    {
        $this->authenticateOperator();
        
        $farmer = Farmer::factory()->create();

        Debt::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => 'OUTSTANDING',
            'remaining_amount_fcfa' => 5000.00,
        ]);
        Debt::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => 'OVERDUE',
            'remaining_amount_fcfa' => 3000.00,
        ]);
        Debt::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => 'PAID',
            'remaining_amount_fcfa' => 0.00,
        ]);

        $response = $this->getJson("/api/debts/farmer/{$farmer->id}/summary");

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
                'total_remaining_amount' => 8000.00,
            ]);
    }

    public function test_get_farmer_debt_summary_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/debts/farmer/{$farmer->id}/summary");

        $response->assertStatus(401);
    }

    public function test_get_debt_statistics(): void
    {
        $this->authenticateOperator();
        
        Debt::factory()->create(['original_amount_fcfa' => 10000.00, 'status' => 'OUTSTANDING']);
        Debt::factory()->create(['original_amount_fcfa' => 15000.00, 'status' => 'OVERDUE']);
        Debt::factory()->create(['original_amount_fcfa' => 8000.00, 'status' => 'PAID']);

        $response = $this->getJson('/api/debts/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_debts',
                'total_original_amount',
                'total_paid_amount',
                'total_remaining_amount',
                'outstanding_debts_count',
                'overdue_debts_count',
                'paid_debts_count',
                'average_debt_amount',
                'debt_by_status',
            ])
            ->assertJson([
                'total_debts' => 3,
                'outstanding_debts_count' => 1,
                'overdue_debts_count' => 1,
                'paid_debts_count' => 1,
            ]);
    }

    public function test_get_debt_statistics_without_authentication(): void
    {
        $response = $this->getJson('/api/debts/statistics');

        $response->assertStatus(401);
    }

    public function test_get_total_outstanding(): void
    {
        $this->authenticateOperator();
        
        Debt::factory()->create(['remaining_amount_fcfa' => 5000.00, 'status' => 'OUTSTANDING']);
        Debt::factory()->create(['remaining_amount_fcfa' => 7500.00, 'status' => 'OUTSTANDING']);
        Debt::factory()->create(['remaining_amount_fcfa' => 0.00, 'status' => 'PAID']);

        $response = $this->getJson('/api/debts/total-outstanding');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_outstanding_amount',
                'total_outstanding_count',
            ])
            ->assertJson([
                'total_outstanding_amount' => 12500.00,
                'total_outstanding_count' => 2,
            ]);
    }

    public function test_get_total_outstanding_without_authentication(): void
    {
        $response = $this->getJson('/api/debts/total-outstanding');

        $response->assertStatus(401);
    }

    public function test_get_total_paid(): void
    {
        $this->authenticateOperator();
        
        Debt::factory()->create(['paid_amount_fcfa' => 8000.00, 'status' => 'PAID']);
        Debt::factory()->create(['paid_amount_fcfa' => 12000.00, 'status' => 'PAID']);
        Debt::factory()->create(['paid_amount_fcfa' => 2000.00, 'status' => 'OUTSTANDING']);

        $response = $this->getJson('/api/debts/total-paid');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_paid_amount',
                'total_paid_count',
            ])
            ->assertJson([
                'total_paid_amount' => 20000.00,
                'total_paid_count' => 2,
            ]);
    }

    public function test_get_total_paid_without_authentication(): void
    {
        $response = $this->getJson('/api/debts/total-paid');

        $response->assertStatus(401);
    }
}
