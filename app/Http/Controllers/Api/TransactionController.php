<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Transaction\TransactionResource;
use App\Http\Resources\Api\Transaction\TransactionDetailResource;
use App\Http\Resources\Api\Transaction\TransactionItemResource;
use App\Contracts\Services\Transaction\TransactionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionServiceInterface $transactionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'farmer_id', 'operator_id', 'status', 'payment_method',
            'start_date', 'end_date', 'min_amount', 'max_amount', 'reference'
        ]);
        
        $transactions = $request->has('page')
            ? $this->transactionService->getPaginated($filters, $request->get('per_page', 15))
            : $this->transactionService->getAll($filters);

        $response = $request->has('page')
            ? $transactions
            : TransactionResource::collection($transactions);

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'operator_id' => 'required|exists:users,id',
            'payment_method' => 'required|in:cash,credit',
            'interest_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'transacted_at' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price_fcfa' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

        $transaction = $this->transactionService->create($validated, $validated['items']);

        return response()->json(TransactionDetailResource::make($transaction), 201);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = $this->transactionService->findById($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable.'], 404);
        }

        return response()->json(TransactionDetailResource::make($transaction));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = $this->transactionService->findById($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable.'], 404);
        }

        $validated = $request->validate([
            'farmer_id' => 'sometimes|exists:farmers,id',
            'operator_id' => 'sometimes|exists:users,id',
            'payment_method' => 'sometimes|in:cash,credit',
            'interest_rate' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'sometimes|string',
        ]);

        $updatedTransaction = $this->transactionService->update($transaction, $validated);

        return response()->json(TransactionDetailResource::make($updatedTransaction));
    }

    public function destroy(int $id): JsonResponse
    {
        $transaction = $this->transactionService->findById($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable.'], 404);
        }

        $this->transactionService->delete($transaction);

        return response()->json(['message' => 'Transaction supprimée avec succès.']);
    }

    public function findByReference(string $reference): JsonResponse
    {
        $transaction = $this->transactionService->findByReference($reference);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable.'], 404);
        }

        return response()->json(TransactionDetailResource::make($transaction));
    }

    public function getByFarmer(int $farmerId): JsonResponse
    {
        $transactions = $this->transactionService->getByFarmer($farmerId);

        return response()->json(TransactionResource::collection($transactions));
    }

    public function getByOperator(int $operatorId): JsonResponse
    {
        $transactions = $this->transactionService->getByOperator($operatorId);

        return response()->json(TransactionResource::collection($transactions));
    }

    public function getByStatus(string $status): JsonResponse
    {
        $transactions = $this->transactionService->getByStatus($status);

        return response()->json(TransactionResource::collection($transactions));
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $transactions = $this->transactionService->getByDateRange(
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json(TransactionResource::collection($transactions));
    }

    public function addItem(Request $request, int $transactionId): JsonResponse
    {
        $transaction = $this->transactionService->findById($transactionId);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable.'], 404);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price_fcfa' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        $item = $this->transactionService->addItem($transaction, $validated);

        return response()->json(TransactionItemResource::make($item), 201);
    }

    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        // Note: In a real implementation, you'd need to get the item through the service
        // For now, we'll use the model directly
        $item = \App\Models\TransactionItem::find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Article de transaction introuvable.'], 404);
        }

        $validated = $request->validate([
            'quantity' => 'sometimes|numeric|min:0.001',
            'unit_price_fcfa' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
        ]);

        $updatedItem = $this->transactionService->updateItem($item, $validated);

        return response()->json(TransactionItemResource::make($updatedItem));
    }

    public function removeItem(int $itemId): JsonResponse
    {
        // Note: In a real implementation, you'd need to get the item through the service
        $item = \App\Models\TransactionItem::find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Article de transaction introuvable.'], 404);
        }

        $this->transactionService->removeItem($item);

        return response()->json(['message' => 'Article supprimé avec succès.']);
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->transactionService->getTransactionStats();

        return response()->json($stats);
    }

    public function getTotalByPeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $total = $this->transactionService->getTotalByPeriod(
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json(['total_amount_fcfa' => $total]);
    }
}
