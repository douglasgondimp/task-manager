<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectTasks\IndexRequest;
use App\Http\Requests\ProjectTasks\StoreRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Services\TaskService;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function __construct(protected TaskService $taskService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request, Project $project)
    {
        $tasks = $this->taskService->getTasksByProject($project, $request->validated());

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request, Project $project)
    {
        $task = $project->tasks()->create($request->validated());

        return new TaskResource($task);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
