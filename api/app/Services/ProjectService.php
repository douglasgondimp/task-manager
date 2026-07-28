<?php

namespace App\Services;

use App\Http\Requests\Project\IndexRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class ProjectService
{
    public function getAll(IndexRequest $request)
    {
        $query = Project::query()
            ->withCount('tasks');

        $query = $this->filterProject($query, $request->validated());

        $projects = $query->latest('id')
            ->cursorPaginate($request->input('per_page', 15));

        return $projects;
    }

    private function filterProject(Builder $query, array $filters)
    {
        return $query->when(
            $filters['status'] ?? null,
            function ($query, string $status) {
                $query->where('status', '=', $status);
            }
        )->when(
            $filters['search'] ?? null,
            function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
        );
    }
}
