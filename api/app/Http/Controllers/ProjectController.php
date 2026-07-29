<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\IndexRequest;
use App\Http\Requests\Project\StoreRequest;
use App\Http\Requests\Project\UpdateRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    public function __construct(protected ProjectService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request)
    {
        $projects = $this->service->getAll($request);

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        Project::create($request->validated());

        return response()->json([
            'message' => 'Projeto criado comm sucesso!'
        ], 201);
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
    public function update(UpdateRequest $request, Project $project)
    {
        $project->update($request->validated());

        return new ProjectResource($project->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
