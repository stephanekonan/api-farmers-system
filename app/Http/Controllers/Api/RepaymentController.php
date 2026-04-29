<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Repayment\RepaymentResource;
use App\Http\Resources\Api\Repayment\RepaymentDetailResource;
use App\Contracts\Services\Repayment\RepaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepaymentController extends Controller
{
    public function __construct(private RepaymentServiceInterface $repaymentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'farmer_id', 'operator_id', 'start_date', 'end_date',
            'min_amount', 'max_amount', 'reference', 'min_kg', 'max_kg'
        ]);
        
        $repayments = $request->has('page')
            ? $this->repaymentService->getPaginated($filters, $request->get('per_page', 15))
            : $this->repaymentService->getAll($filters);

        $response = $request->has('page')
            ? $repayments
            : RepaymentResource::collection($repayments);

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'operator_id' => 'required|exists:users,id',
            'commodity_kg' => 'required|numeric|min:0.001',
            'commodity_rate_fcfa_per_kg' => 'required|numeric|min:0',
            'total_fcfa_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'repaid_at' => 'nullable|date',
            'debt_allocations' => 'required|array|min:1',
            'debt_allocations.*.debt_id' => 'required|exists:debts,id',
            'debt_allocations.*.amount_applied_fcfa' => 'required|numeric|min:0.01',
        ]);

        $repayment = $this->repaymentService->create($validated, $validated['debt_allocations']);

        return response()->json(RepaymentDetailResource::make($repayment), 201);
    }

    public function show(int $id): JsonResponse
    {
        $repayment = $this->repaymentService->findById($id);

        if (!$repayment) {
            return response()->json(['message' => 'Remboursement introuvable.'], 404);
        }

        return response()->json(RepaymentDetailResource::make($repayment));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $repayment = $this->repaymentService->findById($id);

        if (!$repayment) {
            return response()->json(['message' => 'Remboursement introuvable.'], 404);
        }

        $validated = $request->validate([
            'farmer_id' => 'sometimes|exists:farmers,id',
            'operator_id' => 'sometimes|exists:users,id',
            'commodity_kg' => 'sometimes|numeric|min:0.001',
            'commodity_rate_fcfa_per_kg' => 'sometimes|numeric|min:0',
            'total_fcfa_value' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
            'repaid_at' => 'sometimes|date',
        ]);

        $updatedRepayment = $this->repaymentService->update($repayment, $validated);

        return response()->json(RepaymentDetailResource::make($updatedRepayment));
    }

    public function destroy(int $id): JsonResponse
    {
        $repayment = $this->repaymentService->findById($id);

        if (!$repayment) {
            return response()->json(['message' => 'Remboursement introuvable.'], 404);
        }

        $this->repaymentService->delete($repayment);

        return response()->json(['message' => 'Remboursement supprimé avec succès.']);
    }

    public function findByReference(string $reference): JsonResponse
    {
        $repayment = $this->repaymentService->findByReference($reference);

        if (!$repayment) {
            return response()->json(['message' => 'Remboursement introuvable.'], 404);
        }

        return response()->json(RepaymentDetailResource::make($repayment));
    }

    public function getByFarmer(int $farmerId): JsonResponse
    {
        $repayments = $this->repaymentService->getByFarmer($farmerId);

        return response()->json(RepaymentResource::collection($repayments));
    }

    public function getByOperator(int $operatorId): JsonResponse
    {
        $repayments = $this->repaymentService->getByOperator($operatorId);

        return response()->json(RepaymentResource::collection($repayments));
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $repayments = $this->repaymentService->getByDateRange(
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json(RepaymentResource::collection($repayments));
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->repaymentService->getRepaymentStats();

        return response()->json($stats);
    }

    public function getTotalRepaidByPeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $total = $this->repaymentService->getTotalRepaidByPeriod(
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json(['total_repaid_fcfa' => $total]);
    }
}
