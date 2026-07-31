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
                    'path',
                    'per_page',
                    'next_cursor',
                    'prev_cursor',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ]
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
     * Test that the created_at filter is only applied when the array is sent.
     */
    public function test_created_at_filter_only_applied_when_array_is_sent(): void
    {
        $project = Project::factory()->create();

        $oldTask = Task::factory()->for($project)->create([
            'created_at' => now()->subMonth(),
        ]);
        $recentTask = Task::factory()->for($project)->create([
            'created_at' => now(),
        ]);

        // Without created_at filter: all tasks should be returned
        $response = $this->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // With created_at filter: only tasks within the date range should be returned
        $startDate = now();

        $response = $this->getJson("/api/projects/{$project->id}/tasks?created_at[]={$startDate}");

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

    /**
     * Test creating a task for a project with valid data.
     */
    public function test_can_create_task_for_project(): void
    {
        $project = Project::factory()->create();

        $taskData = [
            'title' => 'Nova Tarefa',
            'description' => 'Descrição da tarefa',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => now()->addWeek()->format('Y-m-d'),
        ];

        $response = $this->postJson("/api/projects/{$project->id}/tasks", $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'due_date',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Nova Tarefa',
                    'description' => 'Descrição da tarefa',
                    'status' => ['value' => 'todo'],
                    'priority' => ['value' => 'high'],
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Nova Tarefa',
            'status' => 'todo',
            'priority' => 'high',
        ]);
    }

    /**
     * Test creating a task with only required fields.
     */
    public function test_can_create_task_with_only_required_fields(): void
    {
        $project = Project::factory()->create();

        $taskData = [
            'title' => 'Tarefa Apenas Título',
        ];

        $response = $this->postJson("/api/projects/{$project->id}/tasks", $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Tarefa Apenas Título',
                    'description' => null,
                    'status' => ['value' => 'todo'],
                    'priority' => ['value' => 'medium'],
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Tarefa Apenas Título',
            'description' => null,
        ]);
    }

    /**
     * Test creating a task without title fails validation.
     */
    public function test_cannot_create_task_without_title(): void
    {
        $project = Project::factory()->create();

        $taskData = [
            'description' => 'Descrição sem título',
        ];

        $response = $this->postJson("/api/projects/{$project->id}/tasks", $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseMissing('tasks', [
            'description' => 'Descrição sem título',
        ]);
    }

    /**
     * Test creating a task with invalid status fails validation.
     */
    public function test_cannot_create_task_with_invalid_status(): void
    {
        $project = Project::factory()->create();

        $taskData = [
            'title' => 'Tarefa',
            'status' => 'invalid_status',
        ];

        $response = $this->postJson("/api/projects/{$project->id}/tasks", $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseCount('tasks', 0);
    }

    /**
     * Test creating a task with invalid priority fails validation.
     */
    public function test_cannot_create_task_with_invalid_priority(): void
    {
        $project = Project::factory()->create();

        $taskData = [
            'title' => 'Tarefa',
            'priority' => 'invalid_priority',
        ];

        $response = $this->postJson("/api/projects/{$project->id}/tasks", $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);

        $this->assertDatabaseCount('tasks', 0);
    }

    /**
     * Test creating a task with invalid due_date format fails validation.
     */
    public function test_cannot_create_task_with_invalid_due_date(): void
    {
        $project = Project::factory()->create();

        $taskData = [
            'title' => 'Tarefa',
            'due_date' => 'invalid-date',
        ];

        $response = $this->postJson("/api/projects/{$project->id}/tasks", $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);

        $this->assertDatabaseCount('tasks', 0);
    }

    /**
     * Test creating a task for non-existent project returns 404.
     */
    public function test_cannot_create_task_for_non_existent_project(): void
    {
        $taskData = [
            'title' => 'Tarefa',
        ];

        $response = $this->postJson('/api/projects/999/tasks', $taskData);

        $response->assertStatus(404);
    }
}
