<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesJwtAuth;
use Tests\TestCase;

class ExportPerformanceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesJwtAuth;

    /**
     * @group performance
     */
    public function test_export_endpoint_is_reasonably_fast_on_large_dataset(): void
    {
        config(['cache.default' => 'array']);

        $user = User::factory()->create();

        // Seed a medium dataset for CI stability (adjust up locally)
        $keys = 2000;
        $locales = ['en', 'fr', 'es'];
        $tags = ['web', 'mobile', 'desktop'];

        $now = now();

        // Insert tags
        DB::table('tags')->insert(array_map(fn ($t) => [
            'name' => $t, 'created_at' => $now, 'updated_at' => $now,
        ], $tags));

        $tagIds = DB::table('tags')->pluck('id')->toArray();

        // Insert keys + translations + pivot in chunks
        $chunk = 500;
        for ($start = 1; $start <= $keys; $start += $chunk) {
            $end = min($keys, $start + $chunk - 1);

            $keyRows = [];
            $keyNames = [];
            for ($i = $start; $i <= $end; $i++) {
                $name = sprintf('perf.key.%06d', $i);
                $keyNames[] = $name;
                $keyRows[] = ['key' => $name, 'created_at' => $now, 'updated_at' => $now];
            }

            DB::table('translation_keys')->insert($keyRows);

            $keyIdMap = DB::table('translation_keys')
                ->whereIn('key', $keyNames)
                ->pluck('id', 'key')
                ->toArray();

            $translationRows = [];
            $pivotRows = [];

            foreach ($keyNames as $keyName) {
                $keyId = $keyIdMap[$keyName];

                foreach ($locales as $locale) {
                    $translationRows[] = [
                        'translation_key_id' => $keyId,
                        'locale' => $locale,
                        'content' => "[{$locale}] {$keyName}",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // attach 1 random tag
                $pivotRows[] = [
                    'translation_key_id' => $keyId,
                    'tag_id' => (int) $tagIds[array_rand($tagIds)],
                ];
            }

            DB::table('translations')->insert($translationRows);
            DB::table('translation_key_tag')->insert($pivotRows);
        }

        $headers = $this->authHeaders($user);

        $t0 = microtime(true);
        $response = $this->withHeaders($headers)->getJson('/api/translations/export?locale=en');
        $elapsedMs = (microtime(true) - $t0) * 1000;

        $response->assertOk();

        // Keep threshold generous for test environments.
        // Real benchmark targets should be documented in README using MySQL and seeded 100k+ dataset.
        $this->assertLessThan(2500, $elapsedMs, "Export took {$elapsedMs}ms, expected under 2500ms in test env.");
    }
}
