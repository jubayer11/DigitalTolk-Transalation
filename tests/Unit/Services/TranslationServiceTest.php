<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Contracts\Services\TranslationExportServiceInterface;
use App\Models\TranslationKey;
use App\Services\TranslationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function fakeTransaction(): void
    {
        DB::shouldReceive('transaction')
            ->andReturnUsing(fn ($callback) => $callback());
    }

    public function test_create_throws_conflict_when_key_exists(): void
    {
        $this->fakeTransaction();

        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $repo->shouldReceive('findByKey')->with('a.key')->once()->andReturn(new TranslationKey());

        $service = new TranslationService($repo, $export);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Translation key already exists.');

        $service->create([
            'key' => 'a.key',
            'translations' => ['en' => 'Hello'],
            'tags' => ['web'],
        ]);
    }

    public function test_create_creates_key_translations_tags_and_clears_cache(): void
    {
        $this->fakeTransaction();

        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $createdKey = new TranslationKey();
        $createdKey->id = 10;
        $createdKey->key = 'a.key';

        $finalKey = new TranslationKey();
        $finalKey->id = 10;
        $finalKey->key = 'a.key';

        $repo->shouldReceive('findByKey')->with('a.key')->once()->andReturn(null);
        $repo->shouldReceive('createTranslationKey')->with('a.key')->once()->andReturn($createdKey);

        $repo->shouldReceive('updateOrCreateTranslation')->with(10, 'en', 'Hello')->once();
        $repo->shouldReceive('updateOrCreateTranslation')->with(10, 'fr', 'Bonjour')->once();

        $repo->shouldReceive('syncTags')->with(10, ['web', 'mobile'])->once();

        $export->shouldReceive('clearExportCache')->once();

        $repo->shouldReceive('findByKeyWithRelations')->with('a.key')->once()->andReturn($finalKey);

        $service = new TranslationService($repo, $export);

        $result = $service->create([
            'key' => 'a.key',
            'translations' => ['en' => 'Hello', 'fr' => 'Bonjour'],
            'tags' => ['web', 'mobile'],
        ]);

        $this->assertSame('a.key', $result->key);
        $this->assertSame(10, $result->id);
    }

    public function test_update_throws_404_when_key_not_found(): void
    {
        $this->fakeTransaction();

        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $repo->shouldReceive('findByKey')->with('missing.key')->once()->andReturn(null);

        $service = new TranslationService($repo, $export);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Translation key not found.');

        $service->update('missing.key', ['translations' => ['en' => 'Hi']]);
    }

    public function test_update_updates_translations_optional_tags_and_clears_cache(): void
    {
        $this->fakeTransaction();

        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $existing = new TranslationKey();
        $existing->id = 7;
        $existing->key = 'a.key';

        $final = new TranslationKey();
        $final->id = 7;
        $final->key = 'a.key';

        $repo->shouldReceive('findByKey')->with('a.key')->once()->andReturn($existing);

        $repo->shouldReceive('updateOrCreateTranslation')->with(7, 'en', 'Updated')->once();
        $repo->shouldReceive('syncTags')->with(7, ['web'])->once();

        $export->shouldReceive('clearExportCache')->once();

        $repo->shouldReceive('findByKeyWithRelations')->with('a.key')->once()->andReturn($final);

        $service = new TranslationService($repo, $export);

        $result = $service->update('a.key', [
            'translations' => ['en' => 'Updated'],
            'tags' => ['web'],
        ]);

        $this->assertSame('a.key', $result->key);
    }

    public function test_show_returns_404_when_missing(): void
    {
        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $repo->shouldReceive('findByKeyWithRelations')->with('missing.key')->once()->andReturn(null);

        $service = new TranslationService($repo, $export);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Translation key not found.');

        $service->show('missing.key');
    }

    public function test_search_passes_filters_and_per_page(): void
    {
        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $paginator = new LengthAwarePaginator([], 0, 10, 1);

        $repo->shouldReceive('paginateForSearch')
            ->with(['perPage' => 10, 'search' => 'hi'], 10)
            ->once()
            ->andReturn($paginator);

        $service = new TranslationService($repo, $export);

        $result = $service->search(['perPage' => 10, 'search' => 'hi']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_delete_throws_404_when_not_deleted(): void
    {
        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $repo->shouldReceive('deleteByKey')->with('missing.key')->once()->andReturn(false);

        $service = new TranslationService($repo, $export);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Translation key not found.');

        $service->delete('missing.key');
    }

    public function test_delete_clears_cache_when_deleted(): void
    {
        $repo = Mockery::mock(TranslationRepositoryInterface::class);
        $export = Mockery::mock(TranslationExportServiceInterface::class);

        $repo->shouldReceive('deleteByKey')->with('a.key')->once()->andReturn(true);
        $export->shouldReceive('clearExportCache')->once();

        $service = new TranslationService($repo, $export);

        $service->delete('a.key');

        $this->assertTrue(true);
    }
}
