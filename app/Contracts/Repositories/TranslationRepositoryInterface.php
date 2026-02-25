<?php

namespace App\Contracts\Repositories;

use App\Models\TranslationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TranslationRepositoryInterface
{
    public function findByKey(string $key): ?TranslationKey;

    public function findByKeyWithRelations(string $key, array $relations = ['translations', 'tags']): ?TranslationKey;

    public function paginateForSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function createTranslationKey(string $key): TranslationKey;

    public function updateOrCreateTranslation(int $translationKeyId, string $locale, string $content): void;

    public function syncTags(int $translationKeyId, array $tagNames): void;

    public function deleteByKey(string $key): bool;

    public function getExportRows(string $locale, array $tags = []): Collection;
}
