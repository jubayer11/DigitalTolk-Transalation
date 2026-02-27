<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\TranslationExportServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\ExportTranslationRequest;
use Illuminate\Http\JsonResponse;

class TranslationExportController extends Controller
{
    public function __construct(
        protected TranslationExportServiceInterface $translationExportService
    ) {}

    public function export(ExportTranslationRequest $request): JsonResponse
    {
        $validated = $request->validated();


        $locale = $validated['locale'];

        $tags = $validated['tags'] ?? [];

        $export = $this->translationExportService->export($locale, $tags);

        // Return raw key-value JSON for frontend consumption (as requested in the assignment)
        return response()->json($export);
    }
}
