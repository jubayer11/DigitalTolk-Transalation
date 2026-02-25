<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedLargeTranslationDataset extends Command
{
    /**
     * Example:
     * php artisan app:seed-large-translation-dataset --keys=40000 --locales=en,fr,es --tags=web,mobile,desktop --chunk=1000
     */
    protected $signature = 'app:seed-large-translation-dataset
                            {--keys=40000 : Number of translation keys to generate}
                            {--locales=en,fr,es : Comma-separated locales}
                            {--tags=web,mobile,desktop : Comma-separated tags}
                            {--chunk=1000 : Chunk size for bulk inserts}';

    protected $description = 'Seed a large translation dataset using chunked bulk inserts (keys, translations, tags, and pivot attachments).';

    public function handle(): int
    {
        $startTime = microtime(true);

        $totalKeys = (int) $this->option('keys');
        $chunkSize = (int) $this->option('chunk');
        $locales = $this->parseCsvOption((string) $this->option('locales'), true);
        $tags = $this->parseCsvOption((string) $this->option('tags'), true);

        if ($totalKeys < 1) {
            $this->error('The --keys option must be at least 1.');
            return self::FAILURE;
        }

        if ($chunkSize < 1) {
            $this->error('The --chunk option must be at least 1.');
            return self::FAILURE;
        }

        if (empty($locales)) {
            $this->error('At least one locale is required. Example: --locales=en,fr,es');
            return self::FAILURE;
        }

        if (empty($tags)) {
            $this->error('At least one tag is required. Example: --tags=web,mobile,desktop');
            return self::FAILURE;
        }

        if (! $this->requiredTablesExist()) {
            $this->error('Required tables are missing. Please run migrations first.');
            return self::FAILURE;
        }

        $this->info('Starting large translation dataset seeding...');
        $this->newLine();

        $this->line("Keys: {$totalKeys}");
        $this->line('Locales: '.implode(', ', $locales));
        $this->line('Tags: '.implode(', ', $tags));
        $this->line("Chunk size: {$chunkSize}");
        $this->newLine();

        // Ensure tags exist (bulk insert)
        $now = now();
        $tagInsertRows = array_map(fn (string $tag) => [
            'name' => $tag,
            'created_at' => $now,
            'updated_at' => $now,
        ], $tags);

        DB::table('tags')->insertOrIgnore($tagInsertRows);

        /** @var array<string,int> $tagIdMap */
        $tagIdMap = DB::table('tags')
            ->whereIn('name', $tags)
            ->pluck('id', 'name')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (count($tagIdMap) !== count($tags)) {
            $this->error('Could not resolve all tag IDs after inserting tags.');
            return self::FAILURE;
        }

        $pivotHasTimestamps = Schema::hasColumn('translation_key_tag', 'created_at')
            && Schema::hasColumn('translation_key_tag', 'updated_at');

        $keysAttempted = 0;
        $keysInserted = 0;
        $translationsInserted = 0;
        $pivotInserted = 0;

        $chunks = (int) ceil($totalKeys / $chunkSize);

        for ($chunkIndex = 0; $chunkIndex < $chunks; $chunkIndex++) {
            $chunkStart = ($chunkIndex * $chunkSize) + 1;
            $chunkEnd = min(($chunkIndex + 1) * $chunkSize, $totalKeys);
            $currentChunkCount = $chunkEnd - $chunkStart + 1;

            $chunkNow = now();

            // 1) Build translation key rows
            $keyRows = [];
            $chunkKeyNames = [];

            for ($i = $chunkStart; $i <= $chunkEnd; $i++) {
                $keyName = sprintf('translation.key.%06d', $i);

                $chunkKeyNames[] = $keyName;
                $keyRows[] = [
                    'key' => $keyName,
                    'created_at' => $chunkNow,
                    'updated_at' => $chunkNow,
                ];
            }

            DB::transaction(function () use (
                $keyRows,
                $chunkKeyNames,
                $locales,
                $tagIdMap,
                $pivotHasTimestamps,
                $chunkNow,
                &$keysInserted,
                &$translationsInserted,
                &$pivotInserted
            ): void {
                // 2) Insert keys in bulk
                $keysInserted += (int) DB::table('translation_keys')->insertOrIgnore($keyRows);

                // 3) Fetch IDs for inserted/existing keys in this chunk
                /** @var array<string,int> $translationKeyIdMap */
                $translationKeyIdMap = DB::table('translation_keys')
                    ->whereIn('key', $chunkKeyNames)
                    ->pluck('id', 'key')
                    ->map(fn ($id) => (int) $id)
                    ->toArray();

                // 4) Build translations rows in bulk (one row per locale per key)
                $translationRows = [];

                foreach ($chunkKeyNames as $keyName) {
                    $translationKeyId = $translationKeyIdMap[$keyName] ?? null;

                    if (! $translationKeyId) {
                        continue;
                    }

                    foreach ($locales as $locale) {
                        $translationRows[] = [
                            'translation_key_id' => $translationKeyId,
                            'locale' => $locale,
                            'content' => $this->buildTranslationContent($keyName, $locale),
                            'created_at' => $chunkNow,
                            'updated_at' => $chunkNow,
                        ];
                    }
                }

                if (! empty($translationRows)) {
                    $translationsInserted += (int) DB::table('translations')->insertOrIgnore($translationRows);
                }

                // 5) Build pivot rows with random tag assignments
                $tagIds = array_values($tagIdMap);
                $maxTagsPerKey = min(3, count($tagIds));
                $pivotRows = [];

                foreach ($chunkKeyNames as $keyName) {
                    $translationKeyId = $translationKeyIdMap[$keyName] ?? null;

                    if (! $translationKeyId) {
                        continue;
                    }

                    $tagCountForKey = random_int(1, $maxTagsPerKey);
                    $pickedTagIds = $this->pickRandomValues($tagIds, $tagCountForKey);

                    foreach ($pickedTagIds as $tagId) {
                        $row = [
                            'translation_key_id' => $translationKeyId,
                            'tag_id' => (int) $tagId,
                        ];

                        if ($pivotHasTimestamps) {
                            $row['created_at'] = $chunkNow;
                            $row['updated_at'] = $chunkNow;
                        }

                        $pivotRows[] = $row;
                    }
                }

                if (! empty($pivotRows)) {
                    $pivotInserted += (int) DB::table('translation_key_tag')->insertOrIgnore($pivotRows);
                }
            });

            $keysAttempted += $currentChunkCount;

            $processed = min($chunkEnd, $totalKeys);
            $percentage = number_format(($processed / max(1, $totalKeys)) * 100, 2);

            $this->line(sprintf(
                '[Chunk %d/%d] Processed %d/%d keys (%s%%)',
                $chunkIndex + 1,
                $chunks,
                $processed,
                $totalKeys,
                $percentage
            ));
        }

        $elapsedSeconds = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('Seeding completed successfully.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Keys attempted', number_format($keysAttempted)],
                ['Keys inserted', number_format($keysInserted)],
                ['Locales per key', number_format(count($locales))],
                ['Translations inserted', number_format($translationsInserted)],
                ['Pivot rows inserted', number_format($pivotInserted)],
                ['Execution time (seconds)', number_format($elapsedSeconds, 2)],
                ['Approx. translations target', number_format($keysAttempted * count($locales))],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Parse a comma-separated option into a normalized array.
     *
     * @return array<int, string>
     */
    protected function parseCsvOption(string $value, bool $toLower = false): array
    {
        $items = array_map('trim', explode(',', $value));
        $items = array_filter($items, fn ($item) => $item !== '');

        if ($toLower) {
            $items = array_map(fn ($item) => strtolower((string) $item), $items);
        }

        return array_values(array_unique($items));
    }

    /**
     * Generate deterministic translation content.
     */
    protected function buildTranslationContent(string $keyName, string $locale): string
    {
        return sprintf('[%s] Sample translation for %s', strtoupper($locale), $keyName);
    }

    /**
     * Pick N random unique values from an array.
     *
     * @param array<int, mixed> $values
     * @return array<int, mixed>
     */
    protected function pickRandomValues(array $values, int $count): array
    {
        if ($count >= count($values)) {
            return $values;
        }

        $copy = $values;
        shuffle($copy);

        return array_slice($copy, 0, $count);
    }

    protected function requiredTablesExist(): bool
    {
        return Schema::hasTable('translation_keys')
            && Schema::hasTable('translations')
            && Schema::hasTable('tags')
            && Schema::hasTable('translation_key_tag');
    }
}
