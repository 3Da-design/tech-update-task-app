<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWebTest extends TestCase
{
  use RefreshDatabase;

  private User $user;

  protected function setUp(): void
  {
    parent::setUp();

    $this->user = User::factory()->create();
  }

  public function test_index_returns_200_for_authenticated_user(): void
  {
    Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Web task',
      'description' => null,
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => null,
    ]);

    $response = $this->actingAs($this->user)->get('/tasks');

    $response->assertOk();
    $response->assertSee('Web task', false);
  }

  public function test_guest_is_redirected_from_tasks_index(): void
  {
    $response = $this->get('/tasks');

    $response->assertRedirect('/login');
  }

  public function test_store_creates_task_and_redirects(): void
  {
    $response = $this->actingAs($this->user)->post('/tasks', [
      'title' => 'New web task',
      'status' => 'todo',
      'priority' => 'medium',
    ]);

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseHas('tasks', [
      'user_id' => $this->user->id,
      'title' => 'New web task',
    ]);
  }

  public function test_store_without_title_returns_validation_errors(): void
  {
    $response = $this->actingAs($this->user)->post('/tasks', [
      'status' => 'todo',
    ]);

    $response->assertSessionHasErrors('title');
    $this->assertDatabaseCount('tasks', 0);
  }

  public function test_update_changes_task_and_redirects(): void
  {
    $task = Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Before',
      'description' => null,
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => null,
    ]);

    $response = $this->actingAs($this->user)->put("/tasks/{$task->id}", [
      'title' => 'After',
      'status' => 'in_progress',
    ]);

    $response->assertRedirect(route('tasks.index', [
      'title' => 'After',
      'status' => 'in_progress',
    ]));
    $this->assertDatabaseHas('tasks', [
      'id' => $task->id,
      'title' => 'After',
      'status' => 'in_progress',
    ]);
  }

  public function test_destroy_removes_task_and_redirects(): void
  {
    $task = Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'To delete',
      'description' => null,
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => null,
    ]);

    $response = $this->actingAs($this->user)->delete("/tasks/{$task->id}");

    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
  }
}
