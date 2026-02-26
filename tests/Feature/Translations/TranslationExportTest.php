<?php

namespace Tests\Feature\Translations;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesJwtAuth;
use Tests\TestCase;

class TranslationExportTest extends TestCase
{
    use RefreshDatabase;
    use CreatesJwtAuth;

    public function test_export_returns_key_value_json(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'homepage.hero.title',
            'translations' => ['en' => 'Welcome', 'fr' => 'Bienvenue'],
            'tags' => ['web'],
        ])->assertStatus(201);

        $this->getJson('/api/translations/export?locale=en')
            ->assertOk()
            ->assertJson([
                'homepage.hero.title' => 'Welcome',
            ]);
    }

    public function test_export_reflects_updates_after_update(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'homepage.hero.title',
            'translations' => ['en' => 'Welcome'],
            'tags' => ['web'],
        ])->assertStatus(201);

        $this->getJson('/api/translations/export?locale=en')
            ->assertOk()
            ->assertJson(['homepage.hero.title' => 'Welcome']);

        // update translation
        $this->withHeaders($this->authHeaders($user))->putJson('/api/translations/homepage.hero.title', [
            'translations' => ['en' => 'Welcome updated'],
        ])->assertOk();

        // export should return updated result (cache invalidation proof)
        $this->getJson('/api/translations/export?locale=en')
            ->assertOk()
            ->assertJson(['homepage.hero.title' => 'Welcome updated']);
    }

    public function test_export_can_filter_by_tags(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'a.key',
            'translations' => ['en' => 'A'],
            'tags' => ['web'],
        ])->assertStatus(201);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'b.key',
            'translations' => ['en' => 'B'],
            'tags' => ['mobile'],
        ])->assertStatus(201);

        $this->getJson('/api/translations/export?locale=en&tags[]=web')
            ->assertOk()
            ->assertJson(['a.key' => 'A'])
            ->assertJsonMissing(['b.key' => 'B']);
    }
}
