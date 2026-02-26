<?php

namespace Tests\Feature\Translations;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesJwtAuth;
use Tests\TestCase;

class TranslationSearchTest extends TestCase
{
    use RefreshDatabase;
    use CreatesJwtAuth;

    public function test_can_search_by_key_and_content_and_tags(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->withHeaders($this->authHeaders($user))->postJson('/api/translations', [
            'key' => 'dashboard.title',
            'translations' => ['en' => 'Dashboard Home'],
            'tags' => ['web', 'dashboard'],
        ])->assertStatus(201);

        // search by key
        $this->getJson('/api/translations?search=dashboard')
            ->assertOk()
            ->assertJsonFragment(['key' => 'dashboard.title']);

        // search by content
        $this->getJson('/api/translations?search=Home&locale=en')
            ->assertOk()
            ->assertJsonFragment(['key' => 'dashboard.title']);

        // filter by tag
        $this->getJson('/api/translations?tag=dashboard')
            ->assertOk()
            ->assertJsonFragment(['key' => 'dashboard.title']);
    }
}
