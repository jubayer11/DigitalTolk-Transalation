<?php

namespace App\Services;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Contracts\Services\TranslationExportServiceInterface;
use App\Contracts\Services\TranslationServiceInterface;
use App\Models\TranslationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        protected TranslationRepositoryInterface $translationRepository,
        protected TranslationExportServiceInterface $translationExportService,
    ) {}

    public function create(array $payload): TranslationKey
    {
        return DB::transaction(function () use ($payload) {
            $existing = $this->translationRepository->findByKey($payload['key']);
            if ($existing) {
                throw new \InvalidArgumentException('Translation key already exists.');
            }

            $translationKey = $this->translationRepository->createTranslationKey($payload['key']);

            foreach ($payload['translations'] as $locale => $content) {
                $this->translationRepository->updateOrCreateTranslation(
                    $translationKey->id,
                    $locale,
                    $content
                );
            }

            $this->translationRepository->syncTags($translationKey->id, $payload['tags'] ?? []);

            $this->translationExportService->clearExportCache();

            return $this->translationRepository->findByKeyWithRelations($payload['key']);
        });
    }

    public function update(string $key, array $payload): TranslationKey
    {
        return DB::transaction(function () use ($key, $payload) {
            $translationKey = $this->translationRepository->findByKey($key);

            if (! $translationKey) {
                throw new NotFoundHttpException('Translation key not found.');
            }

            foreach ($payload['translations'] ?? [] as $locale => $content) {
                $this->translationRepository->updateOrCreateTranslation(
                    $translationKey->id,
                    $locale,
                    $content
                );
            }

            if (array_key_exists('tags', $payload)) {
                $this->translationRepository->syncTags($translationKey->id, $payload['tags'] ?? []);
            }

            $this->translationExportService->clearExportCache();

            return $this->translationRepository->findByKeyWithRelations($key);
        });
    }

    public function show(string $key): TranslationKey
    {
        $translationKey = $this->translationRepository->findByKeyWithRelations($key);

        if (! $translationKey) {
            throw new NotFoundHttpException('Translation key not found.');
        }

        return $translationKey;
    }

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->translationRepository->paginateForSearch(
            $filters,
            (int) ($filters['perPage'] ?? 15)
        );
    }

    public function delete(string $key): void
    {
        $deleted = $this->translationRepository->deleteByKey($key);

        if (! $deleted) {
            throw new NotFoundHttpException('Translation key not found.');
        }

        $this->translationExportService->clearExportCache();
    }
}
