<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\Farmer;
use App\Models\User;
use App\Models\TransactionItem;
use App\Models\Debt;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_can_be_created_with_factory(): void
    {
        $transaction = Transaction::factory()->create();

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'reference' => $transaction->reference,
            'farmer_id' => $transaction->farmer_id,
            'operator_id' => $transaction->operator_id,
        ]);
    }

    public function test_transaction_fillable_attributes(): void
    {
        $farmer = Farmer::factory()->create();
        $operator = User::factory()->create();

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
            'transacted_at' => now(),
        ];

        $transaction = Transaction::create($transactionData);

        $this->assertEquals('TXN001', $transaction->reference);
        $this->assertEquals($farmer->id, $transaction->farmer_id);
        $this->assertEquals($operator->id, $transaction->operator_id);
        $this->assertEquals(50000.00, $transaction->subtotal_fcfa);
        $this->assertEquals('CASH', $transaction->payment_method);
        $this->assertEquals(0.05, $transaction->interest_rate);
        $this->assertEquals(2500.00, $transaction->interest_amount_fcfa);
        $this->assertEquals(52500.00, $transaction->total_fcfa);
        $this->assertEquals('COMPLETED', $transaction->status);
        $this->assertEquals('Test transaction', $transaction->notes);
        $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->transacted_at);
    }

    public function test_subtotal_fcfa_casting(): void
    {
        $transaction = Transaction::factory()->create(['subtotal_fcfa' => '50000.50']);

        $this->assertIsFloat($transaction->subtotal_fcfa);
        $this->assertEquals(50000.50, $transaction->subtotal_fcfa);
    }

    public function test_interest_rate_casting(): void
    {
        $transaction = Transaction::factory()->create(['interest_rate' => '0.0550']);

        $this->assertIsFloat($transaction->interest_rate);
        $this->assertEquals(0.0550, $transaction->interest_rate);
    }

    public function test_interest_amount_fcfa_casting(): void
    {
        $transaction = Transaction::factory()->create(['interest_amount_fcfa' => '2500.75']);

        $this->assertIsFloat($transaction->interest_amount_fcfa);
        $this->assertEquals(2500.75, $transaction->interest_amount_fcfa);
    }

    public function test_total_fcfa_casting(): void
    {
        $transaction = Transaction::factory()->create(['total_fcfa' => '52500.25']);

        $this->assertIsFloat($transaction->total_fcfa);
        $this->assertEquals(52500.25, $transaction->total_fcfa);
    }

    public function test_transacted_at_casting(): void
    {
        $transaction = Transaction::factory()->create(['transacted_at' => '2024-01-15 10:30:00']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->transacted_at);
        $this->assertEquals('2024-01-15 10:30:00', $transaction->transacted_at->format('Y-m-d H:i:s'));
    }

    public function test_farmer_relationship(): void
    {
        $transaction = Transaction::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $transaction->farmer());
    }

    public function test_farmer_relationship_can_be_populated(): void
    {
        $farmer = Farmer::factory()->create();
        $transaction = Transaction::factory()->create(['farmer_id' => $farmer->id]);

        $relatedFarmer = $transaction->farmer;

        $this->assertInstanceOf(Farmer::class, $relatedFarmer);
        $this->assertEquals($farmer->id, $relatedFarmer->id);
    }

    public function test_operator_relationship(): void
    {
        $transaction = Transaction::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $transaction->operator());
    }

    public function test_operator_relationship_can_be_populated(): void
    {
        $operator = User::factory()->create();
        $transaction = Transaction::factory()->create(['operator_id' => $operator->id]);

        $relatedOperator = $transaction->operator;

        $this->assertInstanceOf(User::class, $relatedOperator);
        $this->assertEquals($operator->id, $relatedOperator->id);
    }

    public function test_transaction_items_relationship(): void
    {
        $transaction = Transaction::factory()->create();
        $this->assertInstanceOf(HasMany::class, $transaction->transactionItems());
    }

    public function test_transaction_items_relationship_can_be_populated(): void
    {
        $transaction = Transaction::factory()->create();
        $item1 = TransactionItem::factory()->create(['transaction_id' => $transaction->id]);
        $item2 = TransactionItem::factory()->create(['transaction_id' => $transaction->id]);

        $items = $transaction->transactionItems;

        $this->assertCount(2, $items);
        $this->assertContains($item1->id, $items->pluck('id'));
        $this->assertContains($item2->id, $items->pluck('id'));
    }

    public function test_debt_relationship(): void
    {
        $transaction = Transaction::factory()->create();
        $this->assertInstanceOf(HasOne::class, $transaction->debt());
    }

    public function test_debt_relationship_can_be_populated(): void
    {
        $transaction = Transaction::factory()->create();
        $debt = Debt::factory()->create(['transaction_id' => $transaction->id]);

        $relatedDebt = $transaction->debt;

        $this->assertInstanceOf(Debt::class, $relatedDebt);
        $this->assertEquals($debt->id, $relatedDebt->id);
    }

    public function test_reference_is_unique(): void
    {
        $reference = 'UNIQUE_REF_001';
        
        Transaction::factory()->create(['reference' => $reference]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Transaction::factory()->create(['reference' => $reference]);
    }

    public function test_transaction_status_can_be_updated(): void
    {
        $transaction = Transaction::factory()->create(['status' => 'PENDING']);

        $transaction->update(['status' => 'COMPLETED']);

        $this->assertEquals('COMPLETED', $transaction->status);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'COMPLETED',
        ]);
    }

    public function test_transaction_can_be_queried_by_status(): void
    {
        $pendingTransaction = Transaction::factory()->create(['status' => 'PENDING']);
        $completedTransaction = Transaction::factory()->create(['status' => 'COMPLETED']);

        $pendingTransactions = Transaction::where('status', 'PENDING')->get();
        $completedTransactions = Transaction::where('status', 'COMPLETED')->get();

        $this->assertCount(1, $pendingTransactions);
        $this->assertCount(1, $completedTransactions);
        $this->assertEquals($pendingTransaction->id, $pendingTransactions->first()->id);
        $this->assertEquals($completedTransaction->id, $completedTransactions->first()->id);
    }

    public function test_transaction_can_be_queried_by_payment_method(): void
    {
        $cashTransaction = Transaction::factory()->create(['payment_method' => 'CASH']);
        $creditTransaction = Transaction::factory()->create(['payment_method' => 'CREDIT']);

        $cashTransactions = Transaction::where('payment_method', 'CASH')->get();
        $creditTransactions = Transaction::where('payment_method', 'CREDIT')->get();

        $this->assertCount(1, $cashTransactions);
        $this->assertCount(1, $creditTransactions);
        $this->assertEquals($cashTransaction->id, $cashTransactions->first()->id);
        $this->assertEquals($creditTransaction->id, $creditTransactions->first()->id);
    }

    public function test_transaction_can_be_queried_by_date_range(): void
    {
        $date1 = '2024-01-15';
        $date2 = '2024-02-15';
        $date3 = '2024-03-15';

        $transaction1 = Transaction::factory()->create(['transacted_at' => $date1]);
        $transaction2 = Transaction::factory()->create(['transacted_at' => $date2]);
        $transaction3 = Transaction::factory()->create(['transacted_at' => $date3]);

        $rangeTransactions = Transaction::whereBetween('transacted_at', [$date1, $date2])->get();

        $this->assertCount(2, $rangeTransactions);
        $this->assertContains($transaction1->id, $rangeTransactions->pluck('id'));
        $this->assertContains($transaction2->id, $rangeTransactions->pluck('id'));
        $this->assertNotContains($transaction3->id, $rangeTransactions->pluck('id'));
    }

    public function test_transaction_can_be_queried_by_farmer(): void
    {
        $farmer1 = Farmer::factory()->create();
        $farmer2 = Farmer::factory()->create();

        $transaction1 = Transaction::factory()->create(['farmer_id' => $farmer1->id]);
        $transaction2 = Transaction::factory()->create(['farmer_id' => $farmer1->id]);
        $transaction3 = Transaction::factory()->create(['farmer_id' => $farmer2->id]);

        $farmer1Transactions = Transaction::where('farmer_id', $farmer1->id)->get();
        $farmer2Transactions = Transaction::where('farmer_id', $farmer2->id)->get();

        $this->assertCount(2, $farmer1Transactions);
        $this->assertCount(1, $farmer2Transactions);
        $this->assertContains($transaction1->id, $farmer1Transactions->pluck('id'));
        $this->assertContains($transaction2->id, $farmer1Transactions->pluck('id'));
        $this->assertEquals($transaction3->id, $farmer2Transactions->first()->id);
    }

    public function test_transaction_can_be_queried_by_operator(): void
    {
        $operator1 = User::factory()->create();
        $operator2 = User::factory()->create();

        $transaction1 = Transaction::factory()->create(['operator_id' => $operator1->id]);
        $transaction2 = Transaction::factory()->create(['operator_id' => $operator1->id]);
        $transaction3 = Transaction::factory()->create(['operator_id' => $operator2->id]);

        $operator1Transactions = Transaction::where('operator_id', $operator1->id)->get();
        $operator2Transactions = Transaction::where('operator_id', $operator2->id)->get();

        $this->assertCount(2, $operator1Transactions);
        $this->assertCount(1, $operator2Transactions);
        $this->assertContains($transaction1->id, $operator1Transactions->pluck('id'));
        $this->assertContains($transaction2->id, $operator1Transactions->pluck('id'));
        $this->assertEquals($transaction3->id, $operator2Transactions->first()->id);
    }
}
