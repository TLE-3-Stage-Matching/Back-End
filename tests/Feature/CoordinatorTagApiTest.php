<?php

namespace Tests\Feature;

use App\Models\StudentTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CoordinatorTagApiTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;
    private string $coordinatorToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinator = User::factory()->coordinator()->create();
        $this->coordinatorToken = JWTAuth::fromUser($this->coordinator);
    }

    public function test_coordinator_can_list_tags(): void
    {
        Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);
        Tag::create(['name' => 'Creative', 'tag_type' => 'trait', 'is_active' => false]);

        $this->apiAsCoordinator()
            ->getJson('/api/v1/coordinator/tags?per_page=10')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_coordinator_can_create_tag(): void
    {
        $this->apiAsCoordinator()
            ->postJson('/api/v1/coordinator/tags', [
                'name' => 'DevOps',
                'tag_type' => 'industry',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'DevOps')
            ->assertJsonPath('data.tag_type', 'industry')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('tags', [
            'name' => 'DevOps',
            'tag_type' => 'industry',
            'is_active' => true,
        ]);
    }

    public function test_coordinator_cannot_create_duplicate_name_and_type_tag(): void
    {
        Tag::create(['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true]);

        $this->apiAsCoordinator()
            ->postJson('/api/v1/coordinator/tags', [
                'name' => 'PHP',
                'tag_type' => 'skill',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_coordinator_can_update_tag(): void
    {
        $tag = Tag::create(['name' => 'Leadership', 'tag_type' => 'trait', 'is_active' => true]);

        $this->apiAsCoordinator()
            ->patchJson("/api/v1/coordinator/tags/{$tag->id}", [
                'name' => 'Leadership Skills',
                'is_active' => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Leadership Skills')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Leadership Skills',
            'is_active' => false,
        ]);
    }

    public function test_coordinator_can_delete_unused_tag(): void
    {
        $tag = Tag::create(['name' => 'Obsolete Tag', 'tag_type' => 'skill', 'is_active' => true]);

        $this->apiAsCoordinator()
            ->deleteJson("/api/v1/coordinator/tags/{$tag->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_coordinator_cannot_delete_tag_that_is_in_use(): void
    {
        $tag = Tag::create(['name' => 'Laravel', 'tag_type' => 'skill', 'is_active' => true]);
        $student = User::factory()->student()->create();

        StudentTag::create([
            'student_user_id' => $student->id,
            'tag_id' => $tag->id,
            'is_active' => true,
            'weight' => 3,
        ]);

        $this->apiAsCoordinator()
            ->deleteJson("/api/v1/coordinator/tags/{$tag->id}")
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Tag is in use and cannot be deleted. Deactivate it instead by setting is_active=false.',
            ]);

        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_non_coordinator_cannot_manage_tags(): void
    {
        $student = User::factory()->student()->create();
        $token = JWTAuth::fromUser($student);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/coordinator/tags', [
            'name' => 'Forbidden Tag',
            'tag_type' => 'skill',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Forbidden. Coordinator role required.']);
    }

    public function test_unauthenticated_user_cannot_manage_tags(): void
    {
        $this->postJson('/api/v1/coordinator/tags', [
            'name' => 'Forbidden Tag',
            'tag_type' => 'skill',
        ])
            ->assertStatus(401);
    }

    private function apiAsCoordinator()
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->coordinatorToken,
            'Accept' => 'application/json',
        ]);
    }
}

