<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class TaskService
{
    public function getTasksByProject(Project $project, array $filters = []): Builder
    {
        $query = Task::query()
            ->where('project_id', $project->id);

        return $this->filterTasks($query, $filters);
    }

    private function filterTasks(Builder $query, array $filters = []): Builder
    {
        return $query
            ->when(
                $filters["search"] ?? null,
                function ($query, string $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            )->when(
                $filters["status"] ?? null,
                function ($query, string $status) {
                    $query->where('status', '=', $status);
                }
            )->when(
                $filters["priority"] ?? null,
                function ($query, string $priority) {
                    $query->where('priority', '=', $priority);
                }
            )->when(
                $filters["is_overdue"] ?? false,
                function ($query) {
                    $query->whereDate('due_date', '<', now())
                        ->where('status', '!=', 'done');
                }
            )->when(
                isset($filters["created_at"]) && is_array($filters["created_at"]),
                function ($query) use ($filters) {
                    $dates = $filters["created_at"];
                    $query->whereBetween('created_at', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
                }
            );
    }
}
