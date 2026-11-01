<?php

/*
| Projects & Tasks web routes (loaded from routes/web.php in the auth + tenant group).
| module:projects,tasks = tenant has the module/feature; controller checks permissions
| and limits non-managers to their own tasks.
*/

use App\Http\Controllers\Projects\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')->name('projects.')->middleware('module:projects,tasks')->group(function () {
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->whereNumber('task')->name('tasks.show');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->whereNumber('task')->name('tasks.update');
    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])->whereNumber('task')->name('tasks.move');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('tasks.destroy');
    Route::post('tasks/{task}/comments', [TaskController::class, 'comment'])->whereNumber('task')->name('tasks.comments.store');
});
