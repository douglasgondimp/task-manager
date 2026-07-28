<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test listing projects with pagination.
     */
    public function test_can_list_projects_with_pagination(): void
    {
        Project::factory()->count(25)->create();

        $response = $this->getJson('/api/projects?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'tasks_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'path',
                    'per_page',
                    'next_cursor',
                ],
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    /**
     * Test listing projects with custom per_page.
     */
    public function test_can_list_projects_with_custom_per_page(): void
    {
        Project::factory()->count(25)->create();

        $response = $this->getJson('/api/projects?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'meta' => [
                    'per_page' => 5,
                ],
            ]);
    }

    /**
     * Test listing projects with default pagination.
     */
    public function test_can_list_projects_with_default_pagination(): void
    {
        Project::factory()->count(5)->create();

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'meta' => [
                    'per_page' => 15,
                ],
            ]);
    }

    /**
     * Test listing projects returns empty array when no projects exist.
     */
    public function test_returns_empty_array_when_no_projects_exist(): void
    {
        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'meta' => [
                    'per_page' => 15,
                ],
            ]);
    }

    /**
     * Test listing projects includes tasks_count.
     */
    public function test_list_projects_includes_tasks_count(): void
    {
        $project = Project::factory()->create();
        $project->tasks()->createMany([
            ['title' => 'Task 1', 'status' => 'todo', 'priority' => 'high'],
            ['title' => 'Task 2', 'status' => 'done', 'priority' => 'medium'],
        ]);

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $project->id,
                        'tasks_count' => 2,
                    ],
                ],
            ]);
    }
}
