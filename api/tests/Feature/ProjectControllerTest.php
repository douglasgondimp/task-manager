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

    /**
     * Test creating a new project with valid data.
     */
    public function test_can_create_project_with_valid_data(): void
    {
        $projectData = [
            'name' => 'Novo Projeto',
            'description' => 'Descrição do projeto',
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Projeto criado comm sucesso!'
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Novo Projeto',
            'description' => 'Descrição do projeto',
            'status' => 'active',
        ]);
    }

    /**
     * Test creating a project with only required fields.
     */
    public function test_can_create_project_with_only_required_fields(): void
    {
        $projectData = [
            'name' => 'Projeto Apenas Nome',
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Projeto criado comm sucesso!'
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Projeto Apenas Nome',
            'description' => null,
            'status' => 'active',
        ]);
    }

    /**
     * Test creating a project without name fails validation.
     */
    public function test_cannot_create_project_without_name(): void
    {
        $projectData = [
            'description' => 'Descrição sem nome',
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseMissing('projects', [
            'description' => 'Descrição sem nome',
        ]);
    }

    /**
     * Test creating a project with name exceeding max length fails.
     */
    public function test_cannot_create_project_with_name_too_long(): void
    {
        $projectData = [
            'name' => str_repeat('a', 51), // 51 characters, max is 50
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('projects', 0);
    }

    /**
     * Test updating a project with valid data.
     */
    public function test_can_update_project_with_valid_data(): void
    {
        $project = Project::factory()->create([
            'name' => 'Projeto Original',
            'description' => 'Descrição original',
        ]);

        $updateData = [
            'name' => 'Projeto Atualizado',
            'description' => 'Nova descrição',
        ];

        $response = $this->patchJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Projeto Atualizado',
            'description' => 'Nova descrição',
        ]);
    }

    /**
     * Test updating only the name of a project.
     */
    public function test_can_update_project_name_only(): void
    {
        $project = Project::factory()->create([
            'name' => 'Nome Original',
            'description' => 'Descrição original',
        ]);

        $updateData = [
            'name' => 'Nome Atualizado',
        ];

        $response = $this->patchJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Nome Atualizado',
            'description' => 'Descrição original',
        ]);
    }

    /**
     * Test updating project status.
     */
    public function test_can_update_project_status(): void
    {
        $project = Project::factory()->create([
            'status' => 'active',
        ]);

        $updateData = [
            'status' => 'archived',
        ];

        $response = $this->patchJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'archived',
        ]);
    }

    /**
     * Test updating a project without any data fails validation.
     */
    public function test_cannot_update_project_without_any_data(): void
    {
        $project = Project::factory()->create();

        $response = $this->patchJson("/api/projects/{$project->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['request']);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => $project->name,
        ]);
    }

    /**
     * Test updating a project with invalid status fails validation.
     */
    public function test_cannot_update_project_with_invalid_status(): void
    {
        $project = Project::factory()->create();

        $updateData = [
            'status' => 'invalid_status',
        ];

        $response = $this->patchJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => $project->status,
        ]);
    }

    /**
     * Test updating a project with name exceeding max length fails.
     */
    public function test_cannot_update_project_with_name_too_long(): void
    {
        $project = Project::factory()->create();

        $updateData = [
            'name' => str_repeat('a', 51), // 51 characters, max is 50
        ];

        $response = $this->patchJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => $project->name,
        ]);
    }

    /**
     * Test updating a non-existent project returns 404.
     */
    public function test_cannot_update_non_existent_project(): void
    {
        $updateData = [
            'name' => 'Nome Atualizado',
        ];

        $response = $this->patchJson('/api/projects/999', $updateData);

        $response->assertStatus(404);
    }
}
