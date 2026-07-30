<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $activeProjects = Project::factory()
            ->count(5)
            ->create([
                'status' => ProjectStatus::Active->value,
            ]);

        Project::factory()
            ->count(2)
            ->create([
                'status' => ProjectStatus::Archived->value,
            ])
            ->each(fn(Project $project) => $this->createTasks($project, 10));

        $activeProjects->each(
            fn(Project $project) => $this->createTasks($project, 45)
        );
    }

    private function createTasks(Project $project, int $quantity): void
    {
        $todoQuantity = (int) ceil($quantity * 0.4);
        $inProgressQuantity = (int) ceil($quantity * 0.3);
        $doneQuantity = $quantity - $todoQuantity - $inProgressQuantity;

        Task::factory()
            ->count($todoQuantity)
            ->for($project)
            ->state(fn() => [
                'status' => TaskStatus::Todo->value,
            ])
            ->create();

        Task::factory()
            ->count($inProgressQuantity)
            ->for($project)
            ->state(fn() => [
                'status' => TaskStatus::InProgress->value,
            ])
            ->create();

        Task::factory()
            ->count($doneQuantity)
            ->for($project)
            ->done()
            ->create();

        // Tarefas atrasadas para testar o filtro de atraso.
        Task::factory()
            ->count(5)
            ->for($project)
            ->overdue()
            ->create();

        // Tarefas prioritárias para testar filtros e indicadores.
        Task::factory()
            ->count(5)
            ->for($project)
            ->highPriority()
            ->state(fn() => [
                'status' => TaskStatus::InProgress->value,
            ])
            ->create();
    }
}
