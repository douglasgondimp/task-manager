<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'title'       => fake()->sentence(4, 6),
            'description' => fake()->paragraph(),
            'status'      => fake()->randomElement(TaskStatus::cases())->value,
            'priority'    => fake()->randomElement(TaskPriority::cases())->value,
            'due_date'    => fake()->dateTimeBetween('+1 week', '+1 month'),
        ];
    }

    /**
     * Indicate that the task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'status'   => TaskStatus::Todo->value,
        ]);
    }

    /**
     * Indicate that the task is done.
     */
    public function done(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TaskStatus::Done->value,
        ]);
    }

    /**
     * Indicate that the task is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    /**
     * Indicate that the task has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => TaskPriority::High->value,
        ]);
    }
}
