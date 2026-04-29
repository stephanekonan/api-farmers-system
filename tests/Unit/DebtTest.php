<?php

namespace Tests\Unit;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Repayment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_debt_can_be_created_with_factory(): void
    {
        $debt = Debt::factory()->create();

        $this->assertInstanceOf(Debt::class, $debt);
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'farmer_id' => $debt->farmer_id,
            'transaction_id' => $debt->transaction_id,
            'original_amount_fcfa' => $debt->original_amount_fcfa,
        ]);
    }

    public function test_debt_fillable_attributes(): void
    {
        $farmer = Farmer::factory()->create();
        $transaction = Transaction::factory()->create();

        $debtData = [
            'farmer_id' => $farmer->id,
            'transaction_id' => $transaction->id,
            'original_amount_fcfa' => 10000.00,
            'paid_amount_fcfa' => 2500.00,
            'remaining_amount_fcfa' => 7500.00,
            'status' => 'OUTSTANDING',
            'incurred_at' => now(),
            'fully_paid_at' => null,
        ];

        $debt = Debt::create($debtData);

        $this->assertEquals($farmer->id, $debt->farmer_id);
        $this->assertEquals($transaction->id, $debt->transaction_id);
        $this->assertEquals(10000.00, $debt->original_amount_fcfa);
        $this->assertEquals(2500.00, $debt->paid_amount_fcfa);
        $this->assertEquals(7500.00, $debt->remaining_amount_fcfa);
        $this->assertEquals('OUTSTANDING', $debt->status);
        $this->assertInstanceOf(Carbon::class, $debt->incurred_at);
        $this->assertNull($debt->fully_paid_at);
    }

    public function test_original_amount_fcfa_casting(): void
    {
        $debt = Debt::factory()->create(['original_amount_fcfa' => '10000.50']);

        $this->assertIsFloat($debt->original_amount_fcfa);
        $this->assertEquals(10000.50, $debt->original_amount_fcfa);
    }

    public function test_paid_amount_fcfa_casting(): void
    {
        $debt = Debt::factory()->create(['paid_amount_fcfa' => '2500.75']);

        $this->assertIsFloat($debt->paid_amount_fcfa);
        $this->assertEquals(2500.75, $debt->paid_amount_fcfa);
    }

    public function test_remaining_amount_fcfa_casting(): void
    {
        $debt = Debt::factory()->create(['remaining_amount_fcfa' => '7500.25']);

        $this->assertIsFloat($debt->remaining_amount_fcfa);
        $this->assertEquals(7500.25, $debt->remaining_amount_fcfa);
    }

    public function test_incurred_at_casting(): void
    {
        $debt = Debt::factory()->create(['incurred_at' => '2024-01-15 10:30:00']);

        $this->assertInstanceOf(Carbon::class, $debt->incurred_at);
        $this->assertEquals('2024-01-15 10:30:00', $debt->incurred_at->format('Y-m-d H:i:s'));
    }

    public function test_fully_paid_at_casting(): void
    {
        $debt = Debt::factory()->create(['fully_paid_at' => '2024-02-15 14:20:00']);

        $this->assertInstanceOf(Carbon::class, $debt->fully_paid_at);
        $this->assertEquals('2024-02-15 14:20:00', $debt->fully_paid_at->format('Y-m-d H:i:s'));
    }

    public function test_fully_paid_at_can_be_null(): void
    {
        $debt = Debt::factory()->create(['fully_paid_at' => null]);

        $this->assertNull($debt->fully_paid_at);
    }

    public function test_farmer_relationship(): void
    {
        $debt = Debt::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $debt->farmer());
    }

    public function test_farmer_relationship_can_be_populated(): void
    {
        $farmer = Farmer::factory()->create();
        $debt = Debt::factory()->create(['farmer_id' => $farmer->id]);

        $relatedFarmer = $debt->farmer;

        $this->assertInstanceOf(Farmer::class, $relatedFarmer);
        $this->assertEquals($farmer->id, $relatedFarmer->id);
    }

    public function test_transaction_relationship(): void
    {
        $debt = Debt::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $debt->transaction());
    }

    public function test_transaction_relationship_can_be_populated(): void
    {
        $transaction = Transaction::factory()->create();
        $debt = Debt::factory()->create(['transaction_id' => $transaction->id]);

        $relatedTransaction = $debt->transaction;

        $this->assertInstanceOf(Transaction::class, $relatedTransaction);
        $this->assertEquals($transaction->id, $relatedTransaction->id);
    }

    public function test_repayments_relationship(): void
    {
        $debt = Debt::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $debt->repayments());
    }

    public function test_repayments_relationship_can_be_populated(): void
    {
        $debt = Debt::factory()->create();
        $repayment1 = Repayment::factory()->create();
        $repayment2 = Repayment::factory()->create();

        $debt->repayments()->attach($repayment1->id, ['amount_applied_fcfa' => 1000.00]);
        $debt->repayments()->attach($repayment2->id, ['amount_applied_fcfa' => 1500.00]);

        $repayments = $debt->repayments;

        $this->assertCount(2, $repayments);
        $this->assertContains($repayment1->id, $repayments->pluck('id'));
        $this->assertContains($repayment2->id, $repayments->pluck('id'));
    }

    public function test_repayments_relationship_includes_pivot_data(): void
    {
        $debt = Debt::factory()->create();
        $repayment = Repayment::factory()->create();
        $amountApplied = 1000.00;

        $debt->repayments()->attach($repayment->id, ['amount_applied_fcfa' => $amountApplied]);

        $relatedRepayment = $debt->repayments()->first();
        $pivot = $relatedRepayment->pivot;

        $this->assertEquals($amountApplied, $pivot->amount_applied_fcfa);
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    public function test_debt_status_can_be_updated(): void
    {
        $debt = Debt::factory()->create(['status' => 'OUTSTANDING']);

        $debt->update(['status' => 'PAID']);

        $this->assertEquals('PAID', $debt->status);
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => 'PAID',
        ]);
    }

    public function test_debt_can_be_marked_fully_paid(): void
    {
        $debt = Debt::factory()->create([
            'status' => 'OUTSTANDING',
            'fully_paid_at' => null,
            'remaining_amount_fcfa' => 1000.00,
        ]);

        $debt->update([
            'status' => 'PAID',
            'fully_paid_at' => now(),
            'remaining_amount_fcfa' => 0.00,
        ]);

        $this->assertEquals('PAID', $debt->status);
        $this->assertInstanceOf(Carbon::class, $debt->fully_paid_at);
        $this->assertEquals(0.00, $debt->remaining_amount_fcfa);
    }

    public function test_debt_can_be_queried_by_status(): void
    {
        $outstandingDebt = Debt::factory()->create(['status' => 'OUTSTANDING']);
        $paidDebt = Debt::factory()->create(['status' => 'PAID']);
        $overdueDebt = Debt::factory()->create(['status' => 'OVERDUE']);

        $outstandingDebts = Debt::where('status', 'OUTSTANDING')->get();
        $paidDebts = Debt::where('status', 'PAID')->get();
        $overdueDebts = Debt::where('status', 'OVERDUE')->get();

        $this->assertCount(1, $outstandingDebts);
        $this->assertCount(1, $paidDebts);
        $this->assertCount(1, $overdueDebts);
        $this->assertEquals($outstandingDebt->id, $outstandingDebts->first()->id);
        $this->assertEquals($paidDebt->id, $paidDebts->first()->id);
        $this->assertEquals($overdueDebt->id, $overdueDebts->first()->id);
    }

    public function test_debt_can_be_queried_by_farmer(): void
    {
        $farmer1 = Farmer::factory()->create();
        $farmer2 = Farmer::factory()->create();

        $debt1 = Debt::factory()->create(['farmer_id' => $farmer1->id]);
        $debt2 = Debt::factory()->create(['farmer_id' => $farmer1->id]);
        $debt3 = Debt::factory()->create(['farmer_id' => $farmer2->id]);

        $farmer1Debts = Debt::where('farmer_id', $farmer1->id)->get();
        $farmer2Debts = Debt::where('farmer_id', $farmer2->id)->get();

        $this->assertCount(2, $farmer1Debts);
        $this->assertCount(1, $farmer2Debts);
        $this->assertContains($debt1->id, $farmer1Debts->pluck('id'));
        $this->assertContains($debt2->id, $farmer1Debts->pluck('id'));
        $this->assertEquals($debt3->id, $farmer2Debts->first()->id);
    }

    public function test_debt_can_be_queried_by_transaction(): void
    {
        $transaction1 = Transaction::factory()->create();
        $transaction2 = Transaction::factory()->create();

        $debt1 = Debt::factory()->create(['transaction_id' => $transaction1->id]);
        $debt2 = Debt::factory()->create(['transaction_id' => $transaction2->id]);

        $transaction1Debts = Debt::where('transaction_id', $transaction1->id)->get();
        $transaction2Debts = Debt::where('transaction_id', $transaction2->id)->get();

        $this->assertCount(1, $transaction1Debts);
        $this->assertCount(1, $transaction2Debts);
        $this->assertEquals($debt1->id, $transaction1Debts->first()->id);
        $this->assertEquals($debt2->id, $transaction2Debts->first()->id);
    }

    public function test_debt_can_be_queried_by_remaining_amount(): void
    {
        $debt1 = Debt::factory()->create(['remaining_amount_fcfa' => 0.00]);
        $debt2 = Debt::factory()->create(['remaining_amount_fcfa' => 5000.00]);
        $debt3 = Debt::factory()->create(['remaining_amount_fcfa' => 10000.00]);

        $paidDebts = Debt::where('remaining_amount_fcfa', 0.00)->get();
        $outstandingDebts = Debt::where('remaining_amount_fcfa', '>', 0.00)->get();

        $this->assertCount(1, $paidDebts);
        $this->assertCount(2, $outstandingDebts);
        $this->assertEquals($debt1->id, $paidDebts->first()->id);
        $this->assertContains($debt2->id, $outstandingDebts->pluck('id'));
        $this->assertContains($debt3->id, $outstandingDebts->pluck('id'));
    }

    public function test_debt_can_be_queried_by_date_range(): void
    {
        $date1 = '2024-01-15';
        $date2 = '2024-02-15';
        $date3 = '2024-03-15';

        $debt1 = Debt::factory()->create(['incurred_at' => $date1]);
        $debt2 = Debt::factory()->create(['incurred_at' => $date2]);
        $debt3 = Debt::factory()->create(['incurred_at' => $date3]);

        $rangeDebts = Debt::whereBetween('incurred_at', [$date1, $date2])->get();

        $this->assertCount(2, $rangeDebts);
        $this->assertContains($debt1->id, $rangeDebts->pluck('id'));
        $this->assertContains($debt2->id, $rangeDebts->pluck('id'));
        $this->assertNotContains($debt3->id, $rangeDebts->pluck('id'));
    }
}
