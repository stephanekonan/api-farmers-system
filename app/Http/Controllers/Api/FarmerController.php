<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Farmer\FarmerResource;
use App\Http\Resources\Api\Farmer\FarmerSummaryResource;
use App\Contracts\Services\Farmer\FarmerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    public function __construct(private FarmerServiceInterface $farmerService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'is_active', 'region', 'village', 'has_debt', 
            'exceeding_credit', 'min_credit_limit', 'max_credit_limit', 'search'
        ]);
        
        $farmers = $request->has('page')
            ? $this->farmerService->getPaginated($filters, $request->get('per_page', 15))
            : $this->farmerService->getAll($filters);

        $response = $request->has('page')
            ? $farmers
            : FarmerResource::collection($farmers);

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_identifier' => 'required|string|max:50',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'village' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'credit_limit_fcfa' => 'required|numeric|min:0',
            'total_outstanding_debt' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $farmer = $this->farmerService->create($validated);

        return response()->json(FarmerResource::make($farmer), 201);
    }

    public function show(int $id): JsonResponse
    {
        $farmer = $this->farmerService->findById($id);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        return response()->json(FarmerResource::make($farmer));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $farmer = $this->farmerService->findById($id);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        $validated = $request->validate([
            'card_identifier' => 'sometimes|string|max:50',
            'firstname' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'village' => 'sometimes|string|max:255',
            'region' => 'sometimes|string|max:255',
            'credit_limit_fcfa' => 'sometimes|numeric|min:0',
            'total_outstanding_debt' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $updatedFarmer = $this->farmerService->update($farmer, $validated);

        return response()->json(FarmerResource::make($updatedFarmer));
    }

    public function destroy(int $id): JsonResponse
    {
        $farmer = $this->farmerService->findById($id);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        $this->farmerService->delete($farmer);

        return response()->json(['message' => 'Farmer deleted successfully.']);
    }

    public function activate(int $id): JsonResponse
    {
        $farmer = $this->farmerService->findById($id);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        $this->farmerService->activate($farmer);

        return response()->json(['message' => 'Farmer activated successfully.']);
    }

    public function deactivate(int $id): JsonResponse
    {
        $farmer = $this->farmerService->findById($id);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        $this->farmerService->deactivate($farmer);

        return response()->json(['message' => 'Farmer deactivated successfully.']);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $farmers = $this->farmerService->search($validated['q']);

        return response()->json(FarmerResource::collection($farmers));
    }

    public function findByCardIdentifier(string $cardIdentifier): JsonResponse
    {
        $farmer = $this->farmerService->findByCardIdentifier($cardIdentifier);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        return response()->json(FarmerResource::make($farmer));
    }

    public function findByPhone(string $phone): JsonResponse
    {
        $farmer = $this->farmerService->findByPhone($phone);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        return response()->json(FarmerResource::make($farmer));
    }

    public function getByRegion(string $region): JsonResponse
    {
        $farmers = $this->farmerService->getByRegion($region);

        return response()->json(FarmerSummaryResource::collection($farmers));
    }

    public function getByVillage(string $village): JsonResponse
    {
        $farmers = $this->farmerService->getByVillage($village);

        return response()->json(FarmerSummaryResource::collection($farmers));
    }

    public function withDebt(): JsonResponse
    {
        $farmers = $this->farmerService->getFarmersWithDebt();

        return response()->json(FarmerSummaryResource::collection($farmers));
    }

    public function exceedingCreditLimit(): JsonResponse
    {
        $farmers = $this->farmerService->getFarmersExceedingCreditLimit();

        return response()->json(FarmerSummaryResource::collection($farmers));
    }

    public function updateCreditLimit(Request $request, int $id): JsonResponse
    {
        $farmer = $this->farmerService->findById($id);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        $validated = $request->validate([
            'credit_limit_fcfa' => 'required|numeric|min:0',
        ]);

        $updatedFarmer = $this->farmerService->updateCreditLimit($farmer, $validated['credit_limit_fcfa']);

        return response()->json(FarmerResource::make($updatedFarmer));
    }

    public function updateDebt(Request $request, int $id): JsonResponse
    {
        $farmer = $this->farmerService->findById($id);

        if (!$farmer) {
            return response()->json(['message' => 'Farmer not found.'], 404);
        }

        $validated = $request->validate([
            'total_outstanding_debt' => 'required|numeric|min:0',
        ]);

        $updatedFarmer = $this->farmerService->updateOutstandingDebt($farmer, $validated['total_outstanding_debt']);

        return response()->json(FarmerResource::make($updatedFarmer));
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'total_outstanding_debt' => $this->farmerService->getTotalOutstandingDebt(),
            'total_credit_limit' => $this->farmerService->getTotalCreditLimit(),
            'total_credit_utilization' => $this->farmerService->getTotalCreditLimit() > 0 
                ? round(($this->farmerService->getTotalOutstandingDebt() / $this->farmerService->getTotalCreditLimit()) * 100, 2)
                : 0,
        ]);
    }
}
