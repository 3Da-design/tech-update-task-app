<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
  use RefreshDatabase;

  private User $user;

  protected function setUp(): void
  {
    parent::setUp();

    $this->user = User::factory()->create([
      'email' => 'test@example.com',
    ]);
  }

  /** 使用表: GET 一覧 200・件数 */
  public function test_index_returns_200_and_task_count(): void
  {
    Task::query()->create([
      'user_id' => User::where('email', 'test@example.com')->value('id'),
      'title' => 'A',
      'description' => null,
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => null,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/tasks');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
  }

  /** POST 正常( title + status は必須に合わせる) */
  public function test_store_returns_201_with_created_task(): void
  {
    $response = $this->actingAs($this->user)->postJson('/api/tasks', [
      'title' => 'New Task',
      'status' => 'todo',
      'priority' => 'medium',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.title', 'New Task');
    $this->assertDatabaseHas('tasks', ['title' => 'New Task']);
  }

  /** POST status 不正 → 422 */
  public function test_store_with_invalid_status_returns_422(): void
  {
    $response = $this->actingAs($this->user)->postJson('/api/tasks', [
      'title' => 't',
      'status' => 'invalid',
      'priority' => 'medium',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('status');
  }

  /** POST title なし → Laravel 標準は 422 */
  public function test_store_without_title_returns_422(): void
  {
    $response = $this->actingAs($this->user)->postJson('/api/tasks', [
      'status' => 'todo',
      'priority' => 'medium',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('title');
  }

  /** PUT 正常 */
  public function test_update_returns_200(): void
  {
    $userId = User::where('email', 'test@example.com')->value('id');
    $task = Task::query()->create([
      'user_id' => $userId,
      'title' => 'Old',
      'description' => null,
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => null,
    ]);

    $response = $this->actingAs($this->user)->putJson("/api/tasks/{$task->id}", [
      'title' => 'Updated',
      'status' => 'in_progress',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Updated');
  }

  /** PUT　存在しない ID → 404(ModelNotFoundException) */
  public function test_update_unknown_id_returns_404(): void
  {
    $response = $this->actingAs($this->user)->putJson('/api/tasks/999999', [
      'title' => 'X',
      'status' => 'todo',
    ]);

    $response->assertNotFound();
  }

  /** DELETE 正常 → 204、その後 DB にないこと */
  public function test_destroy_return_204_and_removes_row(): void
  {
    $userId = User::where('email', 'test@example.com')->value('id');
    $task = Task::query()->create([
      'user_id' => $userId,
      'title' => 'To delete',
      'description' => null,
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => null,
    ]);

    $response = $this->actingAs($this->user)->deleteJson("/api/tasks/{$task->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
  }
}
