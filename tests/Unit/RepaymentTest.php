<?php

namespace Tests\Unit;

use App\Models\Repayment;
use App\Models\Farmer;
use App\Models\User;
use App\Models\Debt;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RepaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_repayment_can_be_created_with_factory(): void
    {
        $repayment = Repayment::factory()->create();

        $this->assertInstanceOf(Repayment::class, $repayment);
        $this->assertDatabaseHas('repayments', [
            'id' => $repayment->id,
            'reference' => $repayment->reference,
            'farmer_id' => $repayment->farmer_id,
            'operator_id' => $repayment->operator_id,
        ]);
    }

    public function test_repayment_fillable_attributes(): void
    {
        $farmer = Farmer::factory()->create();
        $operator = User::factory()->create();

        $repaymentData = [
            'reference' => 'REP001',
            'farmer_id' => $farmer->id,
            'operator_id' => $operator->id,
            'commodity_kg' => 50.500,
            'commodity_rate_fcfa_per_kg' => 250.75,
            'total_fcfa_value' => 12663.125,
            'notes' => 'Test repayment',
            'repaid_at' => now(),
        ];

        $repayment = Repayment::create($repaymentData);

        $this->assertEquals('REP001', $repayment->reference);
        $this->assertEquals($farmer->id, $repayment->farmer_id);
        $this->assertEquals($operator->id, $repayment->operator_id);
        $this->assertEquals(50.500, $repayment->commodity_kg);
        $this->assertEquals(250.75, $repayment->commodity_rate_fcfa_per_kg);
        $this->assertEquals(12663.125, $repayment->total_fcfa_value);
        $this->assertEquals('Test repayment', $repayment->notes);
        $this->assertInstanceOf(\Carbon\Carbon::class, $repayment->repaid_at);
    }

    public function test_commodity_kg_casting(): void
    {
        $repayment = Repayment::factory()->create(['commodity_kg' => '50.500']);

        $this->assertIsFloat($repayment->commodity_kg);
        $this->assertEquals(50.500, $repayment->commodity_kg);
    }

    public function test_commodity_rate_fcfa_per_kg_casting(): void
    {
        $repayment = Repayment::factory()->create(['commodity_rate_fcfa_per_kg' => '250.75']);

        $this->assertIsFloat($repayment->commodity_rate_fcfa_per_kg);
        $this->assertEquals(250.75, $repayment->commodity_rate_fcfa_per_kg);
    }

    public function test_total_fcfa_value_casting(): void
    {
        $repayment = Repayment::factory()->create(['total_fcfa_value' => '12663.125']);

        $this->assertIsFloat($repayment->total_fcfa_value);
        $this->assertEquals(12663.125, $repayment->total_fcfa_value);
    }

    public function test_repaid_at_casting(): void
    {
        $repayment = Repayment::factory()->create(['repaid_at' => '2024-01-15 10:30:00']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $repayment->repaid_at);
        $this->assertEquals('2024-01-15 10:30:00', $repayment->repaid_at->format('Y-m-d H:i:s'));
    }

    public function test_farmer_relationship(): void
    {
        $repayment = Repayment::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $repayment->farmer());
    }

    public function test_farmer_relationship_can_be_populated(): void
    {
        $farmer = Farmer::factory()->create();
        $repayment = Repayment::factory()->create(['farmer_id' => $farmer->id]);

        $relatedFarmer = $repayment->farmer;

        $this->assertInstanceOf(Farmer::class, $relatedFarmer);
        $this->assertEquals($farmer->id, $relatedFarmer->id);
    }

    public function test_operator_relationship(): void
    {
        $repayment = Repayment::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $repayment->operator());
    }

    public function test_operator_relationship_can_be_populated(): void
    {
        $operator = User::factory()->create();
        $repayment = Repayment::factory()->create(['operator_id' => $operator->id]);

        $relatedOperator = $repayment->operator;

        $this->assertInstanceOf(User::class, $relatedOperator);
        $this->assertEquals($operator->id, $relatedOperator->id);
    }

    public function test_debts_relationship(): void
    {
        $repayment = Repayment::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $repayment->debts());
    }

    public function test_debts_relationship_can_be_populated(): void
    {
        $repayment = Repayment::factory()->create();
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();

        $repayment->debts()->attach($debt1->id, ['amount_applied_fcfa' => 5000.00]);
        $repayment->debts()->attach($debt2->id, ['amount_applied_fcfa' => 3000.00]);

        $debts = $repayment->debts;

        $this->assertCount(2, $debts);
        $this->assertContains($debt1->id, $debts->pluck('id'));
        $this->assertContains($debt2->id, $debts->pluck('id'));
    }

    public function test_debts_relationship_includes_pivot_data(): void
    {
        $repayment = Repayment::factory()->create();
        $debt = Debt::factory()->create();
        $amountApplied = 5000.00;

        $repayment->debts()->attach($debt->id, ['amount_applied_fcfa' => $amountApplied]);

        $relatedDebt = $repayment->debts()->first();
        $pivot = $relatedDebt->pivot;

        $this->assertEquals($amountApplied, $pivot->amount_applied_fcfa);
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    public function test_reference_is_unique(): void
    {
        $reference = 'UNIQUE_REP_001';
        
        Repayment::factory()->create(['reference' => $reference]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Repayment::factory()->create(['reference' => $reference]);
    }

    public function test_repayment_can_be_queried_by_farmer(): void
    {
        $farmer1 = Farmer::factory()->create();
        $farmer2 = Farmer::factory()->create();

        $repayment1 = Repayment::factory()->create(['farmer_id' => $farmer1->id]);
        $repayment2 = Repayment::factory()->create(['farmer_id' => $farmer1->id]);
        $repayment3 = Repayment::factory()->create(['farmer_id' => $farmer2->id]);

        $farmer1Repayments = Repayment::where('farmer_id', $farmer1->id)->get();
        $farmer2Repayments = Repayment::where('farmer_id', $farmer2->id)->get();

        $this->assertCount(2, $farmer1Repayments);
        $this->assertCount(1, $farmer2Repayments);
        $this->assertContains($repayment1->id, $farmer1Repayments->pluck('id'));
        $this->assertContains($repayment2->id, $farmer1Repayments->pluck('id'));
        $this->assertEquals($repayment3->id, $farmer2Repayments->first()->id);
    }

    public function test_repayment_can_be_queried_by_operator(): void
    {
        $operator1 = User::factory()->create();
        $operator2 = User::factory()->create();

        $repayment1 = Repayment::factory()->create(['operator_id' => $operator1->id]);
        $repayment2 = Repayment::factory()->create(['operator_id' => $operator1->id]);
        $repayment3 = Repayment::factory()->create(['operator_id' => $operator2->id]);

        $operator1Repayments = Repayment::where('operator_id', $operator1->id)->get();
        $operator2Repayments = Repayment::where('operator_id', $operator2->id)->get();

        $this->assertCount(2, $operator1Repayments);
        $this->assertCount(1, $operator2Repayments);
        $this->assertContains($repayment1->id, $operator1Repayments->pluck('id'));
        $this->assertContains($repayment2->id, $operator1Repayments->pluck('id'));
        $this->assertEquals($repayment3->id, $operator2Repayments->first()->id);
    }

    public function test_repayment_can_be_queried_by_reference(): void
    {
        $reference1 = 'REP001';
        $reference2 = 'REP002';

        $repayment1 = Repayment::factory()->create(['reference' => $reference1]);
        $repayment2 = Repayment::factory()->create(['reference' => $reference2]);

        $foundRepayment1 = Repayment::where('reference', $reference1)->first();
        $foundRepayment2 = Repayment::where('reference', $reference2)->first();

        $this->assertEquals($repayment1->id, $foundRepayment1->id);
        $this->assertEquals($repayment2->id, $foundRepayment2->id);
    }

    public function test_repayment_can_be_queried_by_date_range(): void
    {
        $date1 = '2024-01-15';
        $date2 = '2024-02-15';
        $date3 = '2024-03-15';

        $repayment1 = Repayment::factory()->create(['repaid_at' => $date1]);
        $repayment2 = Repayment::factory()->create(['repaid_at' => $date2]);
        $repayment3 = Repayment::factory()->create(['repaid_at' => $date3]);

        $rangeRepayments = Repayment::whereBetween('repaid_at', [$date1, $date2])->get();

        $this->assertCount(2, $rangeRepayments);
        $this->assertContains($repayment1->id, $rangeRepayments->pluck('id'));
        $this->assertContains($repayment2->id, $rangeRepayments->pluck('id'));
        $this->assertNotContains($repayment3->id, $rangeRepayments->pluck('id'));
    }

    public function test_repayment_can_be_queried_by_commodity_kg_range(): void
    {
        $repayment1 = Repayment::factory()->create(['commodity_kg' => 25.500]);
        $repayment2 = Repayment::factory()->create(['commodity_kg' => 50.000]);
        $repayment3 = Repayment::factory()->create(['commodity_kg' => 75.250]);

        $smallRepayments = Repayment::where('commodity_kg', '<=', 50.000)->get();
        $largeRepayments = Repayment::where('commodity_kg', '>', 50.000)->get();

        $this->assertCount(2, $smallRepayments);
        $this->assertCount(1, $largeRepayments);
        $this->assertContains($repayment1->id, $smallRepayments->pluck('id'));
        $this->assertContains($repayment2->id, $smallRepayments->pluck('id'));
        $this->assertEquals($repayment3->id, $largeRepayments->first()->id);
    }

    public function test_repayment_can_be_queried_by_total_fcfa_value_range(): void
    {
        $repayment1 = Repayment::factory()->create(['total_fcfa_value' => 5000.00]);
        $repayment2 = Repayment::factory()->create(['total_fcfa_value' => 10000.00]);
        $repayment3 = Repayment::factory()->create(['total_fcfa_value' => 15000.00]);

        $lowValueRepayments = Repayment::where('total_fcfa_value', '<=', 10000.00)->get();
        $highValueRepayments = Repayment::where('total_fcfa_value', '>', 10000.00)->get();

        $this->assertCount(2, $lowValueRepayments);
        $this->assertCount(1, $highValueRepayments);
        $this->assertContains($repayment1->id, $lowValueRepayments->pluck('id'));
        $this->assertContains($repayment2->id, $lowValueRepayments->pluck('id'));
        $this->assertEquals($repayment3->id, $highValueRepayments->first()->id);
    }

    public function test_repayment_notes_can_be_updated(): void
    {
        $repayment = Repayment::factory()->create(['notes' => 'Original notes']);

        $repayment->update(['notes' => 'Updated notes']);

        $this->assertEquals('Updated notes', $repayment->notes);
        $this->assertDatabaseHas('repayments', [
            'id' => $repayment->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_repayment_commodity_kg_can_be_updated(): void
    {
        $repayment = Repayment::factory()->create(['commodity_kg' => 50.000]);

        $repayment->update(['commodity_kg' => 75.500]);

        $this->assertEquals(75.500, $repayment->commodity_kg);
        $this->assertDatabaseHas('repayments', [
            'id' => $repayment->id,
            'commodity_kg' => 75.500,
        ]);
    }

    public function test_repayment_commodity_rate_can_be_updated(): void
    {
        $repayment = Repayment::factory()->create(['commodity_rate_fcfa_per_kg' => 250.00]);

        $repayment->update(['commodity_rate_fcfa_per_kg' => 275.50]);

        $this->assertEquals(275.50, $repayment->commodity_rate_fcfa_per_kg);
        $this->assertDatabaseHas('repayments', [
            'id' => $repayment->id,
            'commodity_rate_fcfa_per_kg' => 275.50,
        ]);
    }

    public function test_repayment_total_fcfa_value_can_be_updated(): void
    {
        $repayment = Repayment::factory()->create(['total_fcfa_value' => 12500.00]);

        $repayment->update(['total_fcfa_value' => 15000.75]);

        $this->assertEquals(15000.75, $repayment->total_fcfa_value);
        $this->assertDatabaseHas('repayments', [
            'id' => $repayment->id,
            'total_fcfa_value' => 15000.75,
        ]);
    }
}
