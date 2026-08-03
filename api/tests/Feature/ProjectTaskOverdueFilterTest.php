<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskOverdueFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Test that is_overdue=true returns only overdue tasks.
     */
    public function test_is_overdue_true_returns_only_overdue_tasks(): void
    {
        $project = Project::factory()->create();

        $overdueTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa atrasada',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
        ]);
        $futureTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa no prazo',
            'due_date' => '2026-08-15',
        ]);
        $noDueDateTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa sem prazo',
            'due_date' => null,
        ]);
        $doneTaskWithPastDueDate = Task::factory()->for($project)->create([
            'title' => 'Tarefa concluída com prazo antigo',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Done->value,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=true");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $overdueTask->id])
            ->assertJsonMissing(['id' => $futureTask->id])
            ->assertJsonMissing(['id' => $noDueDateTask->id])
            ->assertJsonMissing(['id' => $doneTaskWithPastDueDate->id]);
    }

    /**
     * Test that is_overdue=false does not apply the overdue filter.
     */
    public function test_is_overdue_false_does_not_apply_overdue_filter(): void
    {
        $project = Project::factory()->create();

        $overdueTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa atrasada',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
        ]);
        $futureTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa no prazo',
            'due_date' => '2026-08-15',
        ]);
        $noDueDateTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa sem prazo',
            'due_date' => null,
        ]);
        $doneTaskWithPastDueDate = Task::factory()->for($project)->create([
            'title' => 'Tarefa concluída com prazo antigo',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Done->value,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=false");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['id' => $overdueTask->id])
            ->assertJsonFragment(['id' => $futureTask->id])
            ->assertJsonFragment(['id' => $noDueDateTask->id])
            ->assertJsonFragment(['id' => $doneTaskWithPastDueDate->id]);
    }

    /**
     * Test that omitting is_overdue does not apply the overdue filter.
     */
    public function test_omitting_is_overdue_does_not_apply_overdue_filter(): void
    {
        $project = Project::factory()->create();

        $overdueTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa atrasada',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
        ]);
        $futureTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa no prazo',
            'due_date' => '2026-08-15',
        ]);
        $noDueDateTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa sem prazo',
            'due_date' => null,
        ]);
        $doneTaskWithPastDueDate = Task::factory()->for($project)->create([
            'title' => 'Tarefa concluída com prazo antigo',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Done->value,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['id' => $overdueTask->id])
            ->assertJsonFragment(['id' => $futureTask->id])
            ->assertJsonFragment(['id' => $noDueDateTask->id])
            ->assertJsonFragment(['id' => $doneTaskWithPastDueDate->id]);
    }

    /**
     * Test that is_overdue=true combined with a complete created_at range returns the intersection.
     */
    public function test_is_overdue_true_with_complete_created_at_range(): void
    {
        $project = Project::factory()->create();

        $overdueInsideRange = Task::factory()->for($project)->create([
            'title' => 'Atrasada dentro do intervalo',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $overdueOutsideRange = Task::factory()->for($project)->create([
            'title' => 'Atrasada fora do intervalo',
            'due_date' => '2026-07-10',
            'status' => TaskStatus::Todo->value,
            'created_at' => '2026-07-05 10:00:00',
        ]);
        $notOverdueInsideRange = Task::factory()->for($project)->create([
            'title' => 'No prazo dentro do intervalo',
            'due_date' => '2026-08-15',
            'created_at' => '2026-07-26 10:00:00',
        ]);
        $notOverdueOutsideRange = Task::factory()->for($project)->create([
            'title' => 'No prazo fora do intervalo',
            'due_date' => '2026-08-15',
            'created_at' => '2026-07-05 10:00:00',
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=true&created_at[]=2026-07-20&created_at[]=2026-07-31");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $overdueInsideRange->id])
            ->assertJsonMissing(['id' => $overdueOutsideRange->id])
            ->assertJsonMissing(['id' => $notOverdueInsideRange->id])
            ->assertJsonMissing(['id' => $notOverdueOutsideRange->id]);
    }

    /**
     * Test that is_overdue=false combined with a created_at range applies only the date filter.
     */
    public function test_is_overdue_false_with_created_at_range(): void
    {
        $project = Project::factory()->create();

        $overdueInsideRange = Task::factory()->for($project)->create([
            'title' => 'Atrasada dentro do intervalo',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $overdueOutsideRange = Task::factory()->for($project)->create([
            'title' => 'Atrasada fora do intervalo',
            'due_date' => '2026-07-10',
            'status' => TaskStatus::Todo->value,
            'created_at' => '2026-07-05 10:00:00',
        ]);
        $notOverdueInsideRange = Task::factory()->for($project)->create([
            'title' => 'No prazo dentro do intervalo',
            'due_date' => '2026-08-15',
            'created_at' => '2026-07-26 10:00:00',
        ]);
        $notOverdueOutsideRange = Task::factory()->for($project)->create([
            'title' => 'No prazo fora do intervalo',
            'due_date' => '2026-08-15',
            'created_at' => '2026-07-05 10:00:00',
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=false&created_at[]=2026-07-20&created_at[]=2026-07-31");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $overdueInsideRange->id])
            ->assertJsonFragment(['id' => $notOverdueInsideRange->id])
            ->assertJsonMissing(['id' => $overdueOutsideRange->id])
            ->assertJsonMissing(['id' => $notOverdueOutsideRange->id]);
    }

    /**
     * Test that is_overdue=true combined with status returns the intersection.
     */
    public function test_is_overdue_true_with_status_filter(): void
    {
        $project = Project::factory()->create();

        $overdueTodo = Task::factory()->for($project)->create([
            'title' => 'Atrasada a fazer',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
        ]);
        $overdueInProgress = Task::factory()->for($project)->create([
            'title' => 'Atrasada em desenvolvimento',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::InProgress->value,
        ]);
        $overdueDone = Task::factory()->for($project)->create([
            'title' => 'Atrasada concluída',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Done->value,
        ]);
        $notOverdueTodo = Task::factory()->for($project)->create([
            'title' => 'No prazo a fazer',
            'due_date' => '2026-08-15',
            'status' => TaskStatus::Todo->value,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=true&status=todo");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $overdueTodo->id])
            ->assertJsonMissing(['id' => $overdueInProgress->id])
            ->assertJsonMissing(['id' => $overdueDone->id])
            ->assertJsonMissing(['id' => $notOverdueTodo->id]);
    }

    /**
     * Test that is_overdue=true combined with priority returns the intersection.
     */
    public function test_is_overdue_true_with_priority_filter(): void
    {
        $project = Project::factory()->create();

        $overdueHigh = Task::factory()->for($project)->create([
            'title' => 'Atrasada alta',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
        ]);
        $overdueLow = Task::factory()->for($project)->create([
            'title' => 'Atrasada baixa',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::Low->value,
        ]);
        $notOverdueHigh = Task::factory()->for($project)->create([
            'title' => 'No prazo alta',
            'due_date' => '2026-08-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=true&priority=high");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $overdueHigh->id])
            ->assertJsonMissing(['id' => $overdueLow->id])
            ->assertJsonMissing(['id' => $notOverdueHigh->id]);
    }

    /**
     * Test that is_overdue=true combined with textual search returns the intersection.
     */
    public function test_is_overdue_true_with_search_filter(): void
    {
        $project = Project::factory()->create();

        $overdueMatching = Task::factory()->for($project)->create([
            'title' => 'Tarefa urgente para revisar',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
        ]);
        $overdueNotMatching = Task::factory()->for($project)->create([
            'title' => 'Outra atividade',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
        ]);
        $notOverdueMatching = Task::factory()->for($project)->create([
            'title' => 'Tarefa urgente para revisar',
            'due_date' => '2026-08-15',
            'status' => TaskStatus::Todo->value,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=true&search=urgente");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $overdueMatching->id])
            ->assertJsonMissing(['id' => $overdueNotMatching->id])
            ->assertJsonMissing(['id' => $notOverdueMatching->id]);
    }

    /**
     * Test that is_overdue=true combined with date range, status, priority and search returns the full intersection.
     */
    public function test_is_overdue_true_with_all_filters_combined(): void
    {
        $project = Project::factory()->create();

        $targetTask = Task::factory()->for($project)->create([
            'title' => 'Tarefa urgente do projeto',
            'description' => 'Precisa ser finalizada',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $notOverdueButMatches = Task::factory()->for($project)->create([
            'title' => 'Tarefa urgente do projeto',
            'description' => 'Precisa ser finalizada',
            'due_date' => '2026-08-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $overdueOutsideRange = Task::factory()->for($project)->create([
            'title' => 'Tarefa urgente do projeto',
            'description' => 'Precisa ser finalizada',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'created_at' => '2026-07-05 10:00:00',
        ]);
        $overdueWrongStatus = Task::factory()->for($project)->create([
            'title' => 'Tarefa urgente do projeto',
            'description' => 'Precisa ser finalizada',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::High->value,
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $overdueWrongPriority = Task::factory()->for($project)->create([
            'title' => 'Tarefa urgente do projeto',
            'description' => 'Precisa ser finalizada',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::Low->value,
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $overdueWrongSearch = Task::factory()->for($project)->create([
            'title' => 'Outra coisa qualquer',
            'description' => 'Sem relação',
            'due_date' => '2026-07-15',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'created_at' => '2026-07-25 10:00:00',
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?is_overdue=true&created_at[]=2026-07-20&created_at[]=2026-07-31&status=todo&priority=high&search=projeto");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $targetTask->id])
            ->assertJsonMissing(['id' => $notOverdueButMatches->id])
            ->assertJsonMissing(['id' => $overdueOutsideRange->id])
            ->assertJsonMissing(['id' => $overdueWrongStatus->id])
            ->assertJsonMissing(['id' => $overdueWrongPriority->id])
            ->assertJsonMissing(['id' => $overdueWrongSearch->id]);
    }

    /**
     * Test that a created_at range with only a start date filters within that single day.
     */
    public function test_created_at_range_with_only_start_date(): void
    {
        $project = Project::factory()->create();

        $insideTask = Task::factory()->for($project)->create([
            'title' => 'Dentro do dia',
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $beforeTask = Task::factory()->for($project)->create([
            'title' => 'Antes do dia',
            'created_at' => '2026-07-10 10:00:00',
        ]);
        $afterTask = Task::factory()->for($project)->create([
            'title' => 'Depois do dia',
            'created_at' => '2026-07-26 10:00:00',
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?created_at[]=2026-07-25");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $insideTask->id])
            ->assertJsonMissing(['id' => $beforeTask->id])
            ->assertJsonMissing(['id' => $afterTask->id]);
    }

    /**
     * Test that a created_at range with only an end date filters within that single day.
     */
    public function test_created_at_range_with_only_end_date(): void
    {
        $project = Project::factory()->create();

        $insideTask = Task::factory()->for($project)->create([
            'title' => 'Dentro do dia',
            'created_at' => '2026-07-25 10:00:00',
        ]);
        $beforeTask = Task::factory()->for($project)->create([
            'title' => 'Antes do dia',
            'created_at' => '2026-07-10 10:00:00',
        ]);
        $afterTask = Task::factory()->for($project)->create([
            'title' => 'Depois do dia',
            'created_at' => '2026-07-26 10:00:00',
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/tasks?created_at[1]=2026-07-25");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $insideTask->id])
            ->assertJsonMissing(['id' => $beforeTask->id])
            ->assertJsonMissing(['id' => $afterTask->id]);
    }
}
