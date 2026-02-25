<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TranslationRepository implements TranslationRepositoryInterface
{
    public function findByKey(string $key): ?TranslationKey
    {
        return TranslationKey::query()->where('key', $key)->first();
    }

    public function findByKeyWithRelations(string $key, array $relations = ['translations', 'tags']): ?TranslationKey
    {
        return TranslationKey::query()
            ->with($relations)
            ->where('key', $key)
            ->first();
    }

    public function paginateForSearch(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $locale = $filters['locale'] ?? null;
        $tag = $filters['tag'] ?? null;
        $tags = $filters['tags'] ?? [];

        return TranslationKey::query()
            ->select(['translation_keys.id', 'translation_keys.key', 'translation_keys.created_at', 'translation_keys.updated_at'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('translation_keys.key', 'like', "%{$search}%")
                        ->orWhereHas('translations', function ($tq) use ($search) {
                            $tq->where('content', 'like', "%{$search}%");
                        });
                });
            })
            ->when($locale, function ($query) use ($locale) {
                $query->whereHas('translations', function ($tq) use ($locale) {
                    $tq->where('locale', $locale);
                });
            })
            ->when($tag, function ($query) use ($tag) {
                $query->whereHas('tags', function ($tq) use ($tag) {
                    $tq->where('name', $tag);
                });
            })
            ->when(! empty($tags), function ($query) use ($tags) {
                $query->whereHas('tags', function ($tq) use ($tags) {
                    $tq->whereIn('name', $tags);
                });
            })
            ->with([
                'tags:id,name',
                'translations' => function ($q) use ($locale) {
                    $q->select(['id', 'translation_key_id', 'locale', 'content'])
                        ->when($locale, fn ($sq) => $sq->where('locale', $locale));
                },
            ])
            ->orderByDesc('translation_keys.id')
            ->paginate($perPage);
    }

    public function createTranslationKey(string $key): TranslationKey
    {
        return TranslationKey::query()->create(['key' => $key]);
    }

    public function updateOrCreateTranslation(int $translationKeyId, string $locale, string $content): void
    {
        Translation::query()->updateOrCreate(
            [
                'translation_key_id' => $translationKeyId,
                'locale' => $locale,
            ],
            [
                'content' => $content,
            ]
        );
    }

    public function syncTags(int $translationKeyId, array $tagNames): void
    {
        $normalizedTags = collect($tagNames)
            ->map(fn ($tag) => strtolower(trim((string) $tag)))
            ->filter()
            ->unique()
            ->values();

        $tagIds = $normalizedTags->map(function (string $tagName) {
            return Tag::query()->firstOrCreate(['name' => $tagName])->id;
        })->all();

        $translationKey = TranslationKey::query()->findOrFail($translationKeyId);
        $translationKey->tags()->sync($tagIds);
    }

    public function deleteByKey(string $key): bool
    {
        $translationKey = $this->findByKey($key);

        if (! $translationKey) {
            return false;
        }

        return (bool) $translationKey->delete();
    }

    public function getExportRows(string $locale, array $tags = []): Collection
    {
        return Translation::query()
            ->join('translation_keys', 'translation_keys.id', '=', 'translations.translation_key_id')
            ->select([
                'translation_keys.key',
                'translations.content',
            ])
            ->where('translations.locale', $locale)
            ->when(! empty($tags), function ($query) use ($tags) {
                $query->join('translation_key_tag', 'translation_key_tag.translation_key_id', '=', 'translation_keys.id')
                    ->join('tags', 'tags.id', '=', 'translation_key_tag.tag_id')
                    ->whereIn('tags.name', $tags)
                    ->distinct();
            })
            ->orderBy('translation_keys.key')
            ->get();
    }
}
