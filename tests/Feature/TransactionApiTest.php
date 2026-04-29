<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\Farmer;
use App\Models\User;
use App\Models\Product;
use App\Models\TransactionItem;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    public function test_index_transactions_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        Transaction::factory()->count(3)->create();

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
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
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonCount(3);
    }

    public function test_index_transactions_without_authentication(): void
    {
        $response = $this->getJson('/api/transactions');

        $response->assertStatus(401);
    }

    public function test_store_transaction_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $farmer = Farmer::factory()->create();
        $operator = $this->authenticateOperator();

        $transactionData = [
            'reference' => 'TXN001',
            'farmer_id' => $farmer->id,
            'operator_id' => $operator->id,
            'subtotal_fcfa' => 50000.00,
            'payment_method' => 'CASH',
            'interest_rate' => 0.05,
            'interest_amount_fcfa' => 2500.00,
            'total_fcfa' => 52500.00,
            'status' => 'COMPLETED',
            'notes' => 'Test transaction',
            'transacted_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
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
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('transactions', [
            'reference' => 'TXN001',
            'farmer_id' => $farmer->id,
            'total_fcfa' => 52500.00,
        ]);
    }

    public function test_store_transaction_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $transactionData = [
            'reference' => 'TXN001',
            'farmer_id' => $farmer->id,
            'total_fcfa' => 52500.00,
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(401);
    }

    public function test_store_transaction_with_validation_errors(): void
    {
        $this->authenticateOperator();

        $response = $this->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'reference',
                'farmer_id',
                'operator_id',
                'total_fcfa',
            ]);
    }

    public function test_store_transaction_with_duplicate_reference(): void
    {
        $this->authenticateOperator();

        $existingTransaction = Transaction::factory()->create(['reference' => 'DUPLICATE001']);

        $transactionData = [
            'reference' => 'DUPLICATE001',
            'farmer_id' => Farmer::factory()->create()->id,
            'operator_id' => User::factory()->create()->id,
            'total_fcfa' => 1000.00,
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reference']);
    }

    public function test_show_transaction_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $transaction = Transaction::factory()->create();

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
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
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'farmer_id' => $transaction->farmer_id,
            ]);
    }

    public function test_show_transaction_without_authentication(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_transaction(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/transactions/999999');

        $response->assertStatus(404);
    }

    public function test_update_transaction_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $transaction = Transaction::factory()->create();

        $updateData = [
            'status' => 'CANCELLED',
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/transactions/{$transaction->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('transactions', array_merge(['id' => $transaction->id], $updateData));
    }

    public function test_update_transaction_without_authentication(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'status' => 'CANCELLED',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_nonexistent_transaction(): void
    {
        $this->authenticateOperator();

        $response = $this->putJson('/api/transactions/999999', [
            'status' => 'CANCELLED',
        ]);

        $response->assertStatus(404);
    }

    public function test_delete_transaction_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $transaction = Transaction::factory()->create();

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_delete_transaction_without_authentication(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(401);
    }

    public function test_delete_nonexistent_transaction(): void
    {
        $this->authenticateOperator();

        $response = $this->deleteJson('/api/transactions/999999');

        $response->assertStatus(404);
    }

    public function test_find_transaction_by_reference(): void
    {
        $this->authenticateOperator();
        
        $transaction = Transaction::factory()->create(['reference' => 'TEST_REF_001']);

        $response = $this->getJson("/api/transactions/reference/TEST_REF_001");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $transaction->id,
                'reference' => 'TEST_REF_001',
            ]);
    }

    public function test_find_transaction_by_nonexistent_reference(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/transactions/reference/NONEXISTENT');

        $response->assertStatus(404);
    }

    public function test_find_transaction_by_reference_without_authentication(): void
    {
        $response = $this->getJson('/api/transactions/reference/TEST_REF');

        $response->assertStatus(401);
    }

    public function test_get_transactions_by_farmer(): void
    {
        $this->authenticateOperator();
        
        $farmer = Farmer::factory()->create();
        $transaction1 = Transaction::factory()->create(['farmer_id' => $farmer->id]);
        $transaction2 = Transaction::factory()->create(['farmer_id' => $farmer->id]);
        Transaction::factory()->create(); // Transaction for different farmer

        $response = $this->getJson("/api/transactions/farmer/{$farmer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'reference',
                    'farmer_id',
                    'operator_id',
                    'total_fcfa',
                    'status',
                    'transacted_at',
                ],
            ]);
    }

    public function test_get_transactions_by_farmer_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/transactions/farmer/{$farmer->id}");

        $response->assertStatus(401);
    }

    public function test_get_transactions_by_operator(): void
    {
        $operator = $this->authenticateOperator();
        
        $transaction1 = Transaction::factory()->create(['operator_id' => $operator->id]);
        $transaction2 = Transaction::factory()->create(['operator_id' => $operator->id]);
        Transaction::factory()->create(['operator_id' => User::factory()->create()->id]); // Transaction for different operator

        $response = $this->getJson("/api/transactions/operator/{$operator->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_get_transactions_by_operator_without_authentication(): void
    {
        $operator = User::factory()->create();

        $response = $this->getJson("/api/transactions/operator/{$operator->id}");

        $response->assertStatus(401);
    }

    public function test_get_transactions_by_status(): void
    {
        $this->authenticateOperator();
        
        Transaction::factory()->create(['status' => 'COMPLETED']);
        Transaction::factory()->create(['status' => 'COMPLETED']);
        Transaction::factory()->create(['status' => 'PENDING']);

        $response = $this->getJson('/api/transactions/status/COMPLETED');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $transaction) {
            $this->assertEquals('COMPLETED', $transaction['status']);
        }
    }

    public function test_get_transactions_by_status_without_authentication(): void
    {
        $response = $this->getJson('/api/transactions/status/COMPLETED');

        $response->assertStatus(401);
    }

    public function test_get_transactions_by_date_range(): void
    {
        $this->authenticateOperator();
        
        $date1 = '2024-01-15';
        $date2 = '2024-02-15';
        $date3 = '2024-03-15';

        $transaction1 = Transaction::factory()->create(['transacted_at' => $date1]);
        $transaction2 = Transaction::factory()->create(['transacted_at' => $date2]);
        Transaction::factory()->create(['transacted_at' => $date3]);

        $response = $this->getJson("/api/transactions/date-range?start_date={$date1}&end_date={$date2}");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'reference',
                    'transacted_at',
                ],
            ]);
    }

    public function test_get_transactions_by_date_range_without_authentication(): void
    {
        $response = $this->getJson('/api/transactions/date-range?start_date=2024-01-01&end_date=2024-12-31');

        $response->assertStatus(401);
    }

    public function test_add_item_to_transaction(): void
    {
        $this->authenticateOperator();
        
        $transaction = Transaction::factory()->create();
        $product = Product::factory()->create();

        $itemData = [
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price_fcfa' => 1000.00,
            'total_fcfa' => 5000.00,
        ];

        $response = $this->postJson("/api/transactions/{$transaction->id}/items", $itemData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'transaction_id',
                'product_id',
                'quantity',
                'unit_price_fcfa',
                'total_fcfa',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('transaction_items', array_merge([
            'transaction_id' => $transaction->id,
        ], $itemData));
    }

    public function test_add_item_to_transaction_without_authentication(): void
    {
        $transaction = Transaction::factory()->create();
        $product = Product::factory()->create();

        $response = $this->postJson("/api/transactions/{$transaction->id}/items", [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(401);
    }

    public function test_update_transaction_item(): void
    {
        $this->authenticateOperator();
        
        $item = TransactionItem::factory()->create();

        $updateData = [
            'quantity' => 10,
            'unit_price_fcfa' => 1500.00,
            'total_fcfa' => 15000.00,
        ];

        $response = $this->putJson("/api/transactions/items/{$item->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('transaction_items', array_merge(['id' => $item->id], $updateData));
    }

    public function test_update_transaction_item_without_authentication(): void
    {
        $item = TransactionItem::factory()->create();

        $response = $this->putJson("/api/transactions/items/{$item->id}", [
            'quantity' => 10,
        ]);

        $response->assertStatus(401);
    }

    public function test_remove_transaction_item(): void
    {
        $this->authenticateOperator();
        
        $item = TransactionItem::factory()->create();

        $response = $this->deleteJson("/api/transactions/items/{$item->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('transaction_items', ['id' => $item->id]);
    }

    public function test_remove_transaction_item_without_authentication(): void
    {
        $item = TransactionItem::factory()->create();

        $response = $this->deleteJson("/api/transactions/items/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_get_transaction_statistics(): void
    {
        $this->authenticateOperator();
        
        Transaction::factory()->create(['total_fcfa' => 10000.00, 'status' => 'COMPLETED']);
        Transaction::factory()->create(['total_fcfa' => 15000.00, 'status' => 'COMPLETED']);
        Transaction::factory()->create(['total_fcfa' => 5000.00, 'status' => 'PENDING']);

        $response = $this->getJson('/api/transactions/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_transactions',
                'total_amount',
                'completed_transactions',
                'pending_transactions',
                'cancelled_transactions',
                'average_transaction_amount',
                'total_by_status',
                'total_by_payment_method',
            ])
            ->assertJson([
                'total_transactions' => 3,
                'completed_transactions' => 2,
                'pending_transactions' => 1,
                'cancelled_transactions' => 0,
            ]);
    }

    public function test_get_transaction_statistics_without_authentication(): void
    {
        $response = $this->getJson('/api/transactions/statistics');

        $response->assertStatus(401);
    }

    public function test_get_total_by_period(): void
    {
        $this->authenticateOperator();
        
        $date1 = '2024-01-15';
        $date2 = '2024-01-20';
        $date3 = '2024-02-15';

        Transaction::factory()->create(['total_fcfa' => 10000.00, 'transacted_at' => $date1]);
        Transaction::factory()->create(['total_fcfa' => 15000.00, 'transacted_at' => $date2]);
        Transaction::factory()->create(['total_fcfa' => 5000.00, 'transacted_at' => $date3]);

        $response = $this->getJson("/api/transactions/total?start_date=2024-01-01&end_date=2024-01-31");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period_start',
                'period_end',
                'total_amount',
                'transaction_count',
                'average_amount',
            ])
            ->assertJson([
                'total_amount' => 25000.00,
                'transaction_count' => 2,
            ]);
    }

    public function test_get_total_by_period_without_authentication(): void
    {
        $response = $this->getJson('/api/transactions/total?start_date=2024-01-01&end_date=2024-12-31');

        $response->assertStatus(401);
    }
}
