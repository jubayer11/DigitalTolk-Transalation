<?php

namespace Tests\Feature\Translations;

use App\Models\Tag;
use App\Models\TranslationKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesJwtAuth;
use Tests\TestCase;

class TranslationCrudTest extends TestCase
{
    use RefreshDatabase;
    use CreatesJwtAuth;

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/translations', [])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_can_create_translation(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $payload = [
            'key' => 'homepage.hero.title',
            'translations' => [
                'en' => 'Welcome',
                'fr' => 'Bienvenue',
                'es' => 'Bienvenido',
            ],
            'tags' => ['web', 'homepage'],
        ];

        $res = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/translations', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('data.key', 'homepage.hero.title')
            ->assertJsonPath('data.translations.en', 'Welcome');
    }

    public function test_duplicate_key_returns_409(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $payload = [
            'key' => 'homepage.hero.title',
            'translations' => ['en' => 'Welcome'],
            'tags' => ['web'],
        ];

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', $payload)->assertStatus(201);
        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', $payload)->assertStatus(409);
    }

    public function test_can_show_translation_by_key(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'homepage.hero.title',
            'translations' => ['en' => 'Welcome'],
            'tags' => ['web'],
        ])->assertStatus(201);

        $this->getJson('/api/translations/homepage.hero.title')
            ->assertOk()
            ->assertJsonPath('data.key', 'homepage.hero.title')
            ->assertJsonPath('data.translations.en', 'Welcome');
    }

    public function test_can_update_translation(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'homepage.hero.title',
            'translations' => ['en' => 'Welcome'],
            'tags' => ['web'],
        ])->assertStatus(201);

        $this->withHeaders($this->authHeaders($user))
            ->putJson('/api/translations/homepage.hero.title', [
                'translations' => ['en' => 'Welcome updated'],
                'tags' => ['web', 'marketing'],
            ])
            ->assertOk()
            ->assertJsonPath('data.translations.en', 'Welcome updated');
    }

    public function test_can_delete_translation(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'homepage.hero.title',
            'translations' => ['en' => 'Welcome'],
            'tags' => ['web'],
        ])->assertStatus(201);

        $this->withHeaders($this->authHeaders($user))
            ->deleteJson('/api/translations/homepage.hero.title')
            ->assertOk();

        $this->getJson('/api/translations/homepage.hero.title')->assertStatus(404);
    }

    public function test_store_validation_errors_return_422(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/translations', [
                'translations' => ['en' => 'Welcome'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.');
    }
}
