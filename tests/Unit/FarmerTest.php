<?php

namespace Tests\Unit;

use App\Models\Farmer;
use App\Models\Transaction;
use App\Models\Debt;
use App\Models\Repayment;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmerTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_be_created_with_factory(): void
    {
        $farmer = Farmer::factory()->create();

        $this->assertInstanceOf(Farmer::class, $farmer);
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'card_identifier' => $farmer->card_identifier,
            'firstname' => $farmer->firstname,
            'lastname' => $farmer->lastname,
        ]);
    }

    public function test_farmer_fillable_attributes(): void
    {
        $farmerData = [
            'card_identifier' => 'FARMER001',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'phone' => '+226123456789',
            'village' => 'Ouagadougou',
            'region' => 'Centre',
            'credit_limit_fcfa' => 100000.50,
            'total_outstanding_debt' => 25000.75,
            'is_active' => true,
        ];

        $farmer = Farmer::create($farmerData);

        $this->assertEquals('FARMER001', $farmer->card_identifier);
        $this->assertEquals('John', $farmer->firstname);
        $this->assertEquals('Doe', $farmer->lastname);
        $this->assertEquals('+226123456789', $farmer->phone);
        $this->assertEquals('Ouagadougou', $farmer->village);
        $this->assertEquals('Centre', $farmer->region);
        $this->assertEquals(100000.50, $farmer->credit_limit_fcfa);
        $this->assertEquals(25000.75, $farmer->total_outstanding_debt);
        $this->assertTrue($farmer->is_active);
    }

    public function test_credit_limit_fcfa_casting(): void
    {
        $farmer = Farmer::factory()->create(['credit_limit_fcfa' => '100000.50']);

        $this->assertIsFloat($farmer->credit_limit_fcfa);
        $this->assertEquals(100000.50, $farmer->credit_limit_fcfa);
    }

    public function test_total_outstanding_debt_casting(): void
    {
        $farmer = Farmer::factory()->create(['total_outstanding_debt' => '25000.75']);

        $this->assertIsFloat($farmer->total_outstanding_debt);
        $this->assertEquals(25000.75, $farmer->total_outstanding_debt);
    }

    public function test_is_active_casting(): void
    {
        $farmer = Farmer::factory()->create(['is_active' => true]);

        $this->assertIsBool($farmer->is_active);
        $this->assertTrue($farmer->is_active);
    }

    public function test_farmer_uses_soft_deletes(): void
    {
        $farmer = Farmer::factory()->create();
        $farmerId = $farmer->id;

        $farmer->delete();

        $this->assertSoftDeleted('farmers', ['id' => $farmerId]);
        $this->assertNotNull($farmer->deleted_at);
    }

    public function test_transactions_relationship(): void
    {
        $farmer = Farmer::factory()->create();
        $this->assertInstanceOf(HasMany::class, $farmer->transactions());
    }

    public function test_transactions_relationship_can_be_populated(): void
    {
        $farmer = Farmer::factory()->create();
        $transaction1 = Transaction::factory()->create(['farmer_id' => $farmer->id]);
        $transaction2 = Transaction::factory()->create(['farmer_id' => $farmer->id]);

        $transactions = $farmer->transactions;

        $this->assertCount(2, $transactions);
        $this->assertContains($transaction1->id, $transactions->pluck('id'));
        $this->assertContains($transaction2->id, $transactions->pluck('id'));
    }

    public function test_debts_relationship(): void
    {
        $farmer = Farmer::factory()->create();
        $this->assertInstanceOf(HasMany::class, $farmer->debts());
    }

    public function test_debts_relationship_can_be_populated(): void
    {
        $farmer = Farmer::factory()->create();
        $debt1 = Debt::factory()->create(['farmer_id' => $farmer->id]);
        $debt2 = Debt::factory()->create(['farmer_id' => $farmer->id]);

        $debts = $farmer->debts;

        $this->assertCount(2, $debts);
        $this->assertContains($debt1->id, $debts->pluck('id'));
        $this->assertContains($debt2->id, $debts->pluck('id'));
    }

    public function test_repayments_relationship(): void
    {
        $farmer = Farmer::factory()->create();
        $this->assertInstanceOf(HasMany::class, $farmer->repayments());
    }

    public function test_repayments_relationship_can_be_populated(): void
    {
        $farmer = Farmer::factory()->create();
        $repayment1 = Repayment::factory()->create(['farmer_id' => $farmer->id]);
        $repayment2 = Repayment::factory()->create(['farmer_id' => $farmer->id]);

        $repayments = $farmer->repayments;

        $this->assertCount(2, $repayments);
        $this->assertContains($repayment1->id, $repayments->pluck('id'));
        $this->assertContains($repayment2->id, $repayments->pluck('id'));
    }

    public function test_card_identifier_is_unique(): void
    {
        $cardIdentifier = 'UNIQUE_CARD_001';
        
        Farmer::factory()->create(['card_identifier' => $cardIdentifier]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Farmer::factory()->create(['card_identifier' => $cardIdentifier]);
    }

    public function test_farmer_can_be_marked_inactive(): void
    {
        $farmer = Farmer::factory()->create(['is_active' => true]);

        $farmer->update(['is_active' => false]);

        $this->assertFalse($farmer->is_active);
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'is_active' => false,
        ]);
    }

    public function test_farmer_credit_limit_can_be_updated(): void
    {
        $farmer = Farmer::factory()->create(['credit_limit_fcfa' => 50000]);

        $farmer->update(['credit_limit_fcfa' => 75000]);

        $this->assertEquals(75000, $farmer->credit_limit_fcfa);
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'credit_limit_fcfa' => 75000,
        ]);
    }

    public function test_farmer_outstanding_debt_can_be_updated(): void
    {
        $farmer = Farmer::factory()->create(['total_outstanding_debt' => 10000]);

        $farmer->update(['total_outstanding_debt' => 15000]);

        $this->assertEquals(15000, $farmer->total_outstanding_debt);
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'total_outstanding_debt' => 15000,
        ]);
    }

    public function test_farmer_can_be_queried_by_active_status(): void
    {
        $activeFarmer = Farmer::factory()->create(['is_active' => true]);
        $inactiveFarmer = Farmer::factory()->create(['is_active' => false]);

        $activeFarmers = Farmer::where('is_active', true)->get();
        $inactiveFarmers = Farmer::where('is_active', false)->get();

        $this->assertCount(1, $activeFarmers);
        $this->assertCount(1, $inactiveFarmers);
        $this->assertEquals($activeFarmer->id, $activeFarmers->first()->id);
        $this->assertEquals($inactiveFarmer->id, $inactiveFarmers->first()->id);
    }

    public function test_farmer_can_be_queried_by_region(): void
    {
        $ouagaFarmer = Farmer::factory()->create(['region' => 'Ouagadougou']);
        $bobFarmer = Farmer::factory()->create(['region' => 'Bobo-Dioulasso']);

        $ouagaFarmers = Farmer::where('region', 'Ouagadougou')->get();
        $bobFarmers = Farmer::where('region', 'Bobo-Dioulasso')->get();

        $this->assertCount(1, $ouagaFarmers);
        $this->assertCount(1, $bobFarmers);
        $this->assertEquals($ouagaFarmer->id, $ouagaFarmers->first()->id);
        $this->assertEquals($bobFarmer->id, $bobFarmers->first()->id);
    }

    public function test_farmer_can_be_queried_by_village(): void
    {
        $village1Farmer = Farmer::factory()->create(['village' => 'Village A']);
        $village2Farmer = Farmer::factory()->create(['village' => 'Village B']);

        $village1Farmers = Farmer::where('village', 'Village A')->get();
        $village2Farmers = Farmer::where('village', 'Village B')->get();

        $this->assertCount(1, $village1Farmers);
        $this->assertCount(1, $village2Farmers);
        $this->assertEquals($village1Farmer->id, $village1Farmers->first()->id);
        $this->assertEquals($village2Farmer->id, $village2Farmers->first()->id);
    }
}
