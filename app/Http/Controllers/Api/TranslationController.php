<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\TranslationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\SearchTranslationRequest;
use App\Http\Requests\Translation\StoreTranslationRequest;
use App\Http\Requests\Translation\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Models\TranslationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class TranslationController extends Controller
{
    public function __construct(
        protected TranslationServiceInterface $translationService
    ) {}

    public function index(SearchTranslationRequest $request): JsonResponse
    {
        $paginator = $this->translationService->search($request->validated());

        return response()->json($this->formatPaginatedResponse($paginator));
    }

    public function show(string $key): JsonResponse
    {

        $translationKey = $this->translationService->show($key);


        return response()->json([
            'message' => 'Translation retrieved successfully.',
            'data' => new TranslationResource($translationKey),
        ]);
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        try {
            $translationKey = $this->translationService->create($request->validated());

            return response()->json([
                'message' => 'Translation created successfully.',
                'data' => new TranslationResource($translationKey),
            ], 201);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
    }

    public function update(UpdateTranslationRequest $request, string $key): JsonResponse
    {
        $translationKey = $this->translationService->update($key, $request->validated());

        return response()->json([
            'message' => 'Translation updated successfully.',
            'data' => new TranslationResource($translationKey),
        ]);
    }

    public function destroy(string $key): JsonResponse
    {
        $this->translationService->delete($key);

        return response()->json([
            'message' => 'Translation deleted successfully.',
        ]);
    }

    /**
     * Format a paginator into a predictable JSON structure.
     */
    protected function formatPaginatedResponse(LengthAwarePaginator $paginator): array
    {
        return [
            'message' => 'Translations retrieved successfully.',
            'data' => TranslationResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
