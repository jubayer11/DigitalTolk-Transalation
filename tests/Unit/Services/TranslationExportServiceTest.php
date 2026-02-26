<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Services\TranslationExportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class TranslationExportServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_export_normalizes_tags_builds_cache_key_and_returns_key_value_array(): void
    {
        $repo = Mockery::mock(TranslationRepositoryInterface::class);

        $rows = collect([
            ['key' => 'a.key', 'content' => 'A'],
            ['key' => 'b.key', 'content' => 'B'],
        ]);

        // normalized tags should be: ['mobile', 'web']
        $repo->shouldReceive('getExportRows')
            ->with('en', ['mobile', 'web'])
            ->once()
            ->andReturn($rows);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($cacheKey, $ttl, $closure) {
                // cacheKey: translations:export:en:<md5(...)>
                return str_starts_with($cacheKey, 'translations:export:en:')
                    && is_callable($closure);
            })
            ->andReturnUsing(function ($cacheKey, $ttl, $closure) {
                return $closure();
            });

        $service = new TranslationExportService($repo);

        $result = $service->export('en', [' Web ', 'mobile', 'web']);

        $this->assertSame([
            'a.key' => 'A',
            'b.key' => 'B',
        ], $result);
    }

    public function test_clear_export_cache_flushes_cache(): void
    {
        $repo = Mockery::mock(TranslationRepositoryInterface::class);

        Cache::shouldReceive('flush')->once();

        $service = new TranslationExportService($repo);

        $service->clearExportCache();

        $this->assertTrue(true);
    }
}
