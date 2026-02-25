<?php

namespace App\Services;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Contracts\Services\TranslationExportServiceInterface;
use Illuminate\Support\Facades\Cache;

class TranslationExportService implements TranslationExportServiceInterface
{
    protected string $cachePrefix = 'translations:export:';

    public function __construct(
        protected TranslationRepositoryInterface $translationRepository
    ) {}

    public function export(string $locale, array $tags = []): array
    {
        $normalizedTags = collect($tags)
            ->map(fn ($tag) => strtolower(trim((string) $tag)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $cacheKey = $this->cachePrefix.$locale.':'.md5(json_encode($normalizedTags));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($locale, $normalizedTags) {
            return $this->translationRepository
                ->getExportRows($locale, $normalizedTags)
                ->pluck('content', 'key')
                ->toArray();
        });
    }

    public function clearExportCache(): void
    {
        // Simple and safe for the test (file cache). For Redis, use tags/prefix strategy.
        // If using file cache, you may clear cache globally:
        Cache::flush();
    }
}
