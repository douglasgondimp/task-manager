<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::controller(ProjectController::class)
        ->prefix('projects')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{project}', 'show');
            Route::patch('/{project}', 'update');
        });

    Route::controller(ProjectTaskController::class)
        ->prefix('projects/{project}')
        ->group(function () {
            Route::get('/tasks', 'index');
            Route::post('/tasks', 'store');
        });

    Route::controller(TaskController::class)
        ->prefix('tasks')
        ->group(function () {
            Route::get('/{task}', 'show');
            Route::patch('/{task}', 'update');
            Route::delete('/{task}', 'destroy');
        });
});
