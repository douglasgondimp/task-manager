<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test listing tasks of a project without filters.
     */
    public function test_can_list_tasks_of_project(): void
    {
        $project = Project::factory()->create();
        Task::factory()->count(3)->for($project)->create();

        $response = $this->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'due_date',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test listing tasks with search filter.
     */
    public function test_can_filter_tasks_by_search(): void
    {
        $project = Project::factory()->create();
        Task::factory()->for($project)->create(['title' => 'Tarefa importante']);
        Task::factory()->for($project)->create(['title' => 'Outra tarefa']);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?search=importante");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'title' => 'Tarefa importante',
                    ],
                ],
            ]);
    }

    /**
     * Test listing tasks with status filter.
     */
    public function test_can_filter_tasks_by_status(): void
    {
        $project = Project::factory()->create();
        Task::factory()->for($project)->create(['status' => 'todo']);
        Task::factory()->for($project)->create(['status' => 'done']);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?status=todo");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'status' => [
                            'value' => 'todo',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test listing tasks with priority filter.
     */
    public function test_can_filter_tasks_by_priority(): void
    {
        $project = Project::factory()->create();
        Task::factory()->for($project)->create(['priority' => 'high']);
        Task::factory()->for($project)->create(['priority' => 'low']);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?priority=high");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'priority' => [
                            'value' => 'high',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test listing overdue tasks.
     */
    public function test_can_filter_overdue_tasks(): void
    {
        $project = Project::factory()->create();
        Task::factory()->for($project)->overdue()->create();
        Task::factory()->for($project)->done()->create();

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=true");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'status' => [
                            'value' => 'todo',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test listing tasks with date range filter.
     */
    public function test_can_filter_tasks_by_date_range(): void
    {
        $project = Project::factory()->create();
        $oldTask = Task::factory()->for($project)->create([
            'created_at' => now()->subMonth(),
        ]);
        $recentTask = Task::factory()->for($project)->create([
            'created_at' => now(),
        ]);

        $startDate = now()->subWeek()->format('Y-m-d');
        $endDate = now()->addWeek()->format('Y-m-d');

        $response = $this->getJson("/api/projects/{$project->id}/tasks?created_at[]={$startDate}&created_at[]={$endDate}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $recentTask->id,
                    ],
                ],
            ]);
    }

    /**
     * Test combining multiple filters.
     */
    public function test_can_combine_multiple_filters(): void
    {
        $project = Project::factory()->create();
        Task::factory()->for($project)->create([
            'title' => 'Tarefa importante',
            'status' => 'todo',
            'priority' => 'high',
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Outra tarefa',
            'status' => 'done',
            'priority' => 'low',
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?search=importante&status=todo&priority=high");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'title' => 'Tarefa importante',
                        'status' => ['value' => 'todo'],
                        'priority' => ['value' => 'high'],
                    ],
                ],
            ]);
    }

    /**
     * Test listing tasks of non-existent project returns 404.
     */
    public function test_cannot_list_tasks_of_non_existent_project(): void
    {
        $response = $this->getJson('/api/projects/999/tasks');

        $response->assertStatus(404);
    }

    /**
     * Test listing tasks returns empty array when project has no tasks.
     */
    public function test_returns_empty_array_when_project_has_no_tasks(): void
    {
        $project = Project::factory()->create();

        $response = $this->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }
}