<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\Debt\DebtServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Debt\AddPaymentRequest;
use App\Http\Requests\Api\Debt\StoreDebtRequest;
use App\Http\Requests\Api\Debt\UpdateDebtRequest;
use App\Http\Resources\Api\Debt\DebtResource;
use App\Http\Resources\Api\Debt\DebtSummaryResource;
use App\Models\Repayment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function __construct(private DebtServiceInterface $debtService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'farmer_id',
            'transaction_id',
            'status',
            'has_outstanding',
            'is_overdue',
            'start_date',
            'end_date',
            'min_amount',
            'max_amount'
        ]);

        $debts = $request->has('page')
            ? $this->debtService->getPaginated($filters, $request->get('per_page', 15))
            : $this->debtService->getAll($filters);

        $response = $request->has('page')
            ? $debts
            : DebtResource::collection($debts);

        return response()->json($response);
    }

    public function store(StoreDebtRequest $request): JsonResponse
    {
        try {
            $debt = $this->debtService->create($request->validated());

            return response()->json(DebtResource::make($debt), 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de la dette.',
                'errors' => method_exists($request, 'errors') ? $request->errors() : null,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $debt = $this->debtService->findById($id);

        if (!$debt) {
            return response()->json(['message' => 'Dette introuvable.'], 404);
        }

        return response()->json(DebtResource::make($debt));
    }

    public function update(UpdateDebtRequest $request, int $id): JsonResponse
    {
        $debt = $this->debtService->findById($id);

        if (!$debt) {
            return response()->json(['message' => 'Dette introuvable.'], 404);
        }

        try {
            $updatedDebt = $this->debtService->update($debt, $request->validated());

            return response()->json(DebtResource::make($updatedDebt));

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de la dette.',
                'errors' => method_exists($request, 'errors') ? $request->errors() : null,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $debt = $this->debtService->findById($id);

        if (!$debt) {
            return response()->json(['message' => 'Dette introuvable.'], 404);
        }

        $this->debtService->delete($debt);

        return response()->json(['message' => 'Dette supprimée avec succès.']);
    }

    public function getByFarmer(int $farmerId): JsonResponse
    {
        $debts = $this->debtService->getByFarmer($farmerId);

        return response()->json(DebtResource::collection($debts));
    }

    public function getByTransaction(int $transactionId): JsonResponse
    {
        $debts = $this->debtService->getByTransaction($transactionId);

        return response()->json(DebtResource::collection($debts));
    }

    public function outstanding(): JsonResponse
    {
        $debts = $this->debtService->getOutstandingDebts();

        return response()->json(DebtSummaryResource::collection($debts));
    }

    public function overdue(): JsonResponse
    {
        $debts = $this->debtService->getOverdueDebts();

        return response()->json(DebtSummaryResource::collection($debts));
    }

    public function paid(): JsonResponse
    {
        $debts = $this->debtService->getPaidDebts();

        return response()->json(DebtResource::collection($debts));
    }

    public function addPayment(AddPaymentRequest $request, int $id): JsonResponse
    {
        $debt = $this->debtService->findById($id);

        if (!$debt) {
            return response()->json(['message' => 'Dette introuvable.'], 404);
        }

        try {
            $repayment = Repayment::find($request->repayment_id);

            if (!$repayment) {
                return response()->json(['message' => 'Remboursement introuvable.'], 404);
            }

            $updatedDebt = $this->debtService->addPayment($debt, $repayment, $request->amount);

            return response()->json(DebtResource::make($updatedDebt));

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'ajout du paiement.',
                'errors' => method_exists($request, 'errors') ? $request->errors() : null,
            ], 500);
        }
    }

    public function updateRemainingAmount(Request $request, int $id): JsonResponse
    {
        $debt = $this->debtService->findById($id);

        if (!$debt) {
            return response()->json(['message' => 'Dette introuvable.'], 404);
        }

        $updatedDebt = $this->debtService->updateRemainingAmount($debt);

        return response()->json(DebtResource::make($updatedDebt));
    }

    public function markAsFullyPaid(int $id): JsonResponse
    {
        $debt = $this->debtService->findById($id);

        if (!$debt) {
            return response()->json(['message' => 'Dette introuvable.'], 404);
        }

        $updatedDebt = $this->debtService->markAsFullyPaid($debt);

        return response()->json(DebtResource::make($updatedDebt));
    }

    public function farmerSummary(int $farmerId): JsonResponse
    {
        $summary = $this->debtService->getFarmerDebtSummary($farmerId);

        return response()->json($summary);
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->debtService->getDebtStats();

        return response()->json($stats);
    }

    public function getTotalOutstanding(): JsonResponse
    {
        $total = $this->debtService->getTotalOutstanding();

        return response()->json(['total_outstanding_fcfa' => $total]);
    }

    public function getTotalPaid(): JsonResponse
    {
        $total = $this->debtService->getTotalPaid();

        return response()->json(['total_paid_fcfa' => $total]);
    }
}
