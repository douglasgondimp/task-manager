<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test displaying a specific task.
     */
    public function test_can_show_task(): void
    {
        $task = Task::factory()->create();

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status' => [
                        'value',
                        'label'
                    ],
                    'priority' => [
                        'value',
                        'label'
                    ],
                    'due_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                ]
            ]);
    }

    /**
     * Test showing a non-existent task returns 404.
     */
    public function test_cannot_show_non_existent_task(): void
    {
        $response = $this->getJson('/api/tasks/999');

        $response->assertStatus(404);
    }

    /**
     * Test updating a task with valid data.
     */
    public function test_can_update_task_with_valid_data(): void
    {
        $task = Task::factory()->create([
            'title' => 'Tarefa Original',
            'description' => 'Descrição original',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $updateData = [
            'title' => 'Tarefa Atualizada',
            'description' => 'Nova descrição',
            'status' => 'in_progress',
            'priority' => 'high',
        ];

        $response = $this->patchJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status' => [
                        'value',
                        'label'
                    ],
                    'priority' => [
                        'value',
                        'label'
                    ],
                    'due_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Tarefa Atualizada',
                    'description' => 'Nova descrição',
                    'status' => [
                        'value' => 'in_progress',
                        'label' => 'Em desenvolvimento'
                    ],
                    'priority' => [
                        'value' => 'high',
                        'label' => 'Alto'
                    ],
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa Atualizada',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    /**
     * Test updating only some fields of a task.
     */
    public function test_can_update_task_partial_fields(): void
    {
        $task = Task::factory()->create([
            'title' => 'Tarefa Original',
            'description' => 'Descrição original',
            'status' => 'todo',
        ]);

        $updateData = [
            'status' => 'done',
        ];

        $response = $this->patchJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Tarefa Original',
                    'description' => 'Descrição original',
                    'status' => [
                        'value' => 'done',
                        'label' => 'Completo'
                    ],
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa Original',
            'status' => 'done',
        ]);
    }

    /**
     * Test updating a non-existent task returns 404.
     */
    public function test_cannot_update_non_existent_task(): void
    {
        $updateData = [
            'title' => 'Tarefa Atualizada',
        ];

        $response = $this->patchJson('/api/tasks/999', $updateData);

        $response->assertStatus(404);
    }

    /**
     * Test updating a task with invalid status fails validation.
     */
    public function test_cannot_update_task_with_invalid_status(): void
    {
        $task = Task::factory()->create();

        $updateData = [
            'status' => 'invalid_status',
        ];

        $response = $this->patchJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => $task->status,
        ]);
    }

    /**
     * Test updating a task with invalid priority fails validation.
     */
    public function test_cannot_update_task_with_invalid_priority(): void
    {
        $task = Task::factory()->create();

        $updateData = [
            'priority' => 'invalid_priority',
        ];

        $response = $this->patchJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => $task->priority,
        ]);
    }

    /**
     * Test deleting a task.
     */
    public function test_can_delete_task(): void
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Task deletada com sucesso.'
            ]);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    /**
     * Test deleting a non-existent task returns 404.
     */
    public function test_cannot_delete_non_existent_task(): void
    {
        $response = $this->deleteJson('/api/tasks/999');

        $response->assertStatus(404);
    }

    /**
     * Test deleting a task performs soft delete.
     */
    public function test_deleted_task_is_soft_deleted(): void
    {
        $task = Task::factory()->create();

        $this->deleteJson("/api/tasks/{$task->id}");

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }
}
