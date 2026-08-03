<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;

class TaskService
{
    public function getTasksByProject(Project $project, array $filters = []): CursorPaginator
    {
        $query = $project->tasks()->getQuery();

        $query = $this->filterTasks($query, $filters);

        return $query->latest('id')
            ->cursorPaginate(10)
            ->withqueryString();
    }

    private function filterTasks(Builder $query, array $filters = []): Builder
    {
        return $query
            ->when(
                $filters['search'] ?? null,
                function ($query, string $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            )->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', '=', $status)

            )->when(
                $filters['priority'] ?? null,
                fn ($query, string $priority) => $query->where('priority', '=', $priority)
            )->when(
                ($filters['is_overdue'] ?? null) === true,
                fn ($query) => $query->overdue()
            )->when(
                isset($filters['created_at']) && is_array($filters['created_at']),
                function ($query) use ($filters) {
                    $dates = $filters['created_at'];
                    $query->whereBetween('created_at', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
                }
            );
    }
}
