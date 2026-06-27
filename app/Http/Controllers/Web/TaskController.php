<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\ParsesTaskRouteId;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
  use ParsesTaskRouteId;

  public function index(IndexTaskRequest $request, TaskService $tasks): View
  {
    return view('tasks.index', [
      'tasks' => $tasks->listForDefaultUser($request->validated()),
    ]);
  }

  public function create(): View
  {
    return view('tasks.create');
  }

  public function store(StoreTaskRequest $request, TaskService $tasks): RedirectResponse
  {
    $tasks->createForDefaultUser($request->validated());

    return redirect()
      ->route('tasks.index')
      ->with('status', 'タスクを作成しました。');
  }

  public function edit(string $id, TaskService $tasks): View
  {
    $task = $tasks->findForDefaultUser($this->parseTaskId($id));

    return view('tasks.edit', compact('task'));
  }

  public function update(UpdateTaskRequest $request, string $id, TaskService $tasks): RedirectResponse
  {
    $tasks->updateForDefaultUser($this->parseTaskId($id), $request->validated());

    return redirect()
      ->route('tasks.index', $request->only(['title', 'status', 'priority', 'priority_sort', 'due_date_sort']))
      ->with('status', 'タスクを更新しました。');
  }

  public function destroy(string $id, TaskService $tasks): RedirectResponse
  {
    $tasks->deleteForDefaultUser($this->parseTaskId($id));

    return redirect()
      ->route('tasks.index')
      ->with('status', 'タスクを削除しました。');
  }
}
