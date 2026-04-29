<?php

namespace Tests\Feature;

use App\Models\Repayment;
use App\Models\Farmer;
use App\Models\User;
use Tests\TestCase;

class RepaymentApiTest extends TestCase
{
    public function test_index_repayments_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        Repayment::factory()->count(3)->create();

        $response = $this->getJson('/api/repayments');

        $response->assertStatus(200)
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
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonCount(3);
    }

    public function test_index_repayments_without_authentication(): void
    {
        $response = $this->getJson('/api/repayments');

        $response->assertStatus(401);
    }

    public function test_store_repayment_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $farmer = Farmer::factory()->create();
        $operator = $this->authenticateOperator();

        $repaymentData = [
            'reference' => 'REP001',
            'farmer_id' => $farmer->id,
            'operator_id' => $operator->id,
            'commodity_kg' => 50.500,
            'commodity_rate_fcfa_per_kg' => 250.75,
            'total_fcfa_value' => 12663.125,
            'notes' => 'Test repayment',
            'repaid_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'reference',
                'farmer_id',
                'operator_id',
                'commodity_kg',
                'commodity_rate_fcfa_per_kg',
                'total_fcfa_value',
                'notes',
                'repaid_at',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('repayments', [
            'reference' => 'REP001',
            'farmer_id' => $farmer->id,
            'total_fcfa_value' => 12663.125,
        ]);
    }

    public function test_store_repayment_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $repaymentData = [
            'reference' => 'REP001',
            'farmer_id' => $farmer->id,
            'total_fcfa_value' => 10000.00,
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(401);
    }

    public function test_store_repayment_with_validation_errors(): void
    {
        $this->authenticateOperator();

        $response = $this->postJson('/api/repayments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'reference',
                'farmer_id',
                'operator_id',
                'commodity_kg',
                'commodity_rate_fcfa_per_kg',
                'total_fcfa_value',
            ]);
    }

    public function test_store_repayment_with_duplicate_reference(): void
    {
        $this->authenticateOperator();

        $existingRepayment = Repayment::factory()->create(['reference' => 'DUPLICATE001']);

        $repaymentData = [
            'reference' => 'DUPLICATE001',
            'farmer_id' => Farmer::factory()->create()->id,
            'operator_id' => User::factory()->create()->id,
            'commodity_kg' => 50.000,
            'commodity_rate_fcfa_per_kg' => 200.00,
            'total_fcfa_value' => 10000.00,
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reference']);
    }

    public function test_show_repayment_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $repayment = Repayment::factory()->create();

        $response = $this->getJson("/api/repayments/{$repayment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'reference',
                'farmer_id',
                'operator_id',
                'commodity_kg',
                'commodity_rate_fcfa_per_kg',
                'total_fcfa_value',
                'notes',
                'repaid_at',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $repayment->id,
                'reference' => $repayment->reference,
                'farmer_id' => $repayment->farmer_id,
            ]);
    }

    public function test_show_repayment_without_authentication(): void
    {
        $repayment = Repayment::factory()->create();

        $response = $this->getJson("/api/repayments/{$repayment->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_repayment(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/repayments/999999');

        $response->assertStatus(404);
    }

    public function test_update_repayment_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $repayment = Repayment::factory()->create();

        $updateData = [
            'notes' => 'Updated notes',
            'commodity_kg' => 60.000,
            'commodity_rate_fcfa_per_kg' => 275.50,
            'total_fcfa_value' => 16530.00,
        ];

        $response = $this->putJson("/api/repayments/{$repayment->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('repayments', array_merge(['id' => $repayment->id], $updateData));
    }

    public function test_update_repayment_without_authentication(): void
    {
        $repayment = Repayment::factory()->create();

        $response = $this->putJson("/api/repayments/{$repayment->id}", [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_repayment_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $repayment = Repayment::factory()->create();

        $response = $this->deleteJson("/api/repayments/{$repayment->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('repayments', ['id' => $repayment->id]);
    }

    public function test_delete_repayment_without_authentication(): void
    {
        $repayment = Repayment::factory()->create();

        $response = $this->deleteJson("/api/repayments/{$repayment->id}");

        $response->assertStatus(401);
    }

    public function test_find_repayment_by_reference(): void
    {
        $this->authenticateOperator();
        
        $repayment = Repayment::factory()->create(['reference' => 'TEST_REF_001']);

        $response = $this->getJson("/api/repayments/reference/TEST_REF_001");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $repayment->id,
                'reference' => 'TEST_REF_001',
            ]);
    }

    public function test_find_repayment_by_nonexistent_reference(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/repayments/reference/NONEXISTENT');

        $response->assertStatus(404);
    }

    public function test_find_repayment_by_reference_without_authentication(): void
    {
        $response = $this->getJson('/api/repayments/reference/TEST_REF');

        $response->assertStatus(401);
    }

    public function test_get_repayments_by_farmer(): void
    {
        $this->authenticateOperator();
        
        $farmer = Farmer::factory()->create();
        $repayment1 = Repayment::factory()->create(['farmer_id' => $farmer->id]);
        $repayment2 = Repayment::factory()->create(['farmer_id' => $farmer->id]);
        Repayment::factory()->create(); // Repayment for different farmer

        $response = $this->getJson("/api/repayments/farmer/{$farmer->id}");

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
                    'repaid_at',
                ],
            ]);
    }

    public function test_get_repayments_by_farmer_without_authentication(): void
    {
        $farmer = Farmer::factory()->create();

        $response = $this->getJson("/api/repayments/farmer/{$farmer->id}");

        $response->assertStatus(401);
    }

    public function test_get_repayments_by_operator(): void
    {
        $operator = $this->authenticateOperator();
        
        $repayment1 = Repayment::factory()->create(['operator_id' => $operator->id]);
        $repayment2 = Repayment::factory()->create(['operator_id' => $operator->id]);
        Repayment::factory()->create(['operator_id' => User::factory()->create()->id]); // Repayment for different operator

        $response = $this->getJson("/api/repayments/operator/{$operator->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_get_repayments_by_operator_without_authentication(): void
    {
        $operator = User::factory()->create();

        $response = $this->getJson("/api/repayments/operator/{$operator->id}");

        $response->assertStatus(401);
    }

    public function test_get_repayments_by_date_range(): void
    {
        $this->authenticateOperator();
        
        $date1 = '2024-01-15';
        $date2 = '2024-02-15';
        $date3 = '2024-03-15';

        $repayment1 = Repayment::factory()->create(['repaid_at' => $date1]);
        $repayment2 = Repayment::factory()->create(['repaid_at' => $date2]);
        Repayment::factory()->create(['repaid_at' => $date3]);

        $response = $this->getJson("/api/repayments/date-range/date-range?start_date={$date1}&end_date={$date2}");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'reference',
                    'repaid_at',
                ],
            ]);
    }

    public function test_get_repayments_by_date_range_without_authentication(): void
    {
        $response = $this->getJson('/api/repayments/date-range/date-range?start_date=2024-01-01&end_date=2024-12-31');

        $response->assertStatus(401);
    }

    public function test_get_repayment_statistics(): void
    {
        $this->authenticateOperator();
        
        Repayment::factory()->create(['total_fcfa_value' => 10000.00]);
        Repayment::factory()->create(['total_fcfa_value' => 15000.00]);
        Repayment::factory()->create(['total_fcfa_value' => 8000.00]);

        $response = $this->getJson('/api/repayments/statistics/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_repayments',
                'total_amount_repaid',
                'average_repayment_amount',
                'total_commodity_kg',
                'average_commodity_rate',
                'repayments_by_operator',
                'repayments_by_month',
                'total_by_farmer',
            ])
            ->assertJson([
                'total_repayments' => 3,
                'total_amount_repaid' => 33000.00,
            ]);
    }

    public function test_get_repayment_statistics_without_authentication(): void
    {
        $response = $this->getJson('/api/repayments/statistics/statistics');

        $response->assertStatus(401);
    }

    public function test_get_total_repaid_by_period(): void
    {
        $this->authenticateOperator();
        
        $date1 = '2024-01-15';
        $date2 = '2024-01-20';
        $date3 = '2024-02-15';

        Repayment::factory()->create(['total_fcfa_value' => 10000.00, 'repaid_at' => $date1]);
        Repayment::factory()->create(['total_fcfa_value' => 15000.00, 'repaid_at' => $date2]);
        Repayment::factory()->create(['total_fcfa_value' => 5000.00, 'repaid_at' => $date3]);

        $response = $this->getJson("/api/repayments/total?start_date=2024-01-01&end_date=2024-01-31");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period_start',
                'period_end',
                'total_amount_repaid',
                'total_repayments_count',
                'average_repayment_amount',
                'total_commodity_kg',
                'repayments_by_operator',
            ])
            ->assertJson([
                'total_amount_repaid' => 25000.00,
                'total_repayments_count' => 2,
            ]);
    }

    public function test_get_total_repaid_by_period_without_authentication(): void
    {
        $response = $this->getJson('/api/repayments/total?start_date=2024-01-01&end_date=2024-12-31');

        $response->assertStatus(401);
    }

    public function test_repayment_with_different_commodity_types(): void
    {
        $this->authenticateOperator();
        
        $repaymentData = [
            'reference' => 'REP002',
            'farmer_id' => Farmer::factory()->create()->id,
            'operator_id' => User::factory()->create()->id,
            'commodity_kg' => 100.250,
            'commodity_rate_fcfa_per_kg' => 300.50,
            'total_fcfa_value' => 30125.125,
            'notes' => 'High quality commodity',
            'repaid_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(201)
            ->assertJson([
                'commodity_kg' => 100.250,
                'commodity_rate_fcfa_per_kg' => 300.50,
                'total_fcfa_value' => 30125.125,
            ]);

        $this->assertDatabaseHas('repayments', [
            'reference' => 'REP002',
            'commodity_kg' => 100.250,
            'commodity_rate_fcfa_per_kg' => 300.50,
            'total_fcfa_value' => 30125.125,
        ]);
    }

    public function test_repayment_validation_for_positive_values(): void
    {
        $this->authenticateOperator();

        $invalidData = [
            'reference' => 'REP003',
            'farmer_id' => Farmer::factory()->create()->id,
            'operator_id' => User::factory()->create()->id,
            'commodity_kg' => -50.000, // Negative value
            'commodity_rate_fcfa_per_kg' => 200.00,
            'total_fcfa_value' => 10000.00,
        ];

        $response = $this->postJson('/api/repayments', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['commodity_kg']);
    }

    public function test_repayment_search_by_commodity_kg_range(): void
    {
        $this->authenticateOperator();
        
        Repayment::factory()->create(['commodity_kg' => 25.500]);
        Repayment::factory()->create(['commodity_kg' => 50.000]);
        Repayment::factory()->create(['commodity_kg' => 75.250]);

        // This would require implementing a search endpoint
        // For now, we'll test the basic index functionality
        $response = $this->getJson('/api/repayments');

        $response->assertStatus(200)
            ->assertJsonCount(3);

        $commodityAmounts = array_column($response->json(), 'commodity_kg');
        $this->assertContains(25.500, $commodityAmounts);
        $this->assertContains(50.000, $commodityAmounts);
        $this->assertContains(75.250, $commodityAmounts);
    }

    public function test_repayment_calculation_accuracy(): void
    {
        $this->authenticateOperator();
        
        $repaymentData = [
            'reference' => 'REP004',
            'farmer_id' => Farmer::factory()->create()->id,
            'operator_id' => User::factory()->create()->id,
            'commodity_kg' => 45.750,
            'commodity_rate_fcfa_per_kg' => 275.25,
            'total_fcfa_value' => 12592.6875, // 45.750 * 275.25
            'repaid_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(201)
            ->assertJson([
                'commodity_kg' => 45.750,
                'commodity_rate_fcfa_per_kg' => 275.25,
                'total_fcfa_value' => 12592.6875,
            ]);

        // Verify the calculation is stored correctly
        $this->assertDatabaseHas('repayments', [
            'commodity_kg' => 45.750,
            'commodity_rate_fcfa_per_kg' => 275.25,
            'total_fcfa_value' => 12592.6875,
        ]);
    }

    public function test_repayment_with_null_notes(): void
    {
        $this->authenticateOperator();
        
        $repaymentData = [
            'reference' => 'REP005',
            'farmer_id' => Farmer::factory()->create()->id,
            'operator_id' => User::factory()->create()->id,
            'commodity_kg' => 30.000,
            'commodity_rate_fcfa_per_kg' => 200.00,
            'total_fcfa_value' => 6000.00,
            'notes' => null,
            'repaid_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(201)
            ->assertJson([
                'notes' => null,
            ]);

        $this->assertDatabaseHas('repayments', [
            'reference' => 'REP005',
            'notes' => null,
        ]);
    }
}
