<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskListFilterTest extends TestCase
{
  use RefreshDatabase;

  private User $user;

  protected function setUp(): void
  {
    parent::setUp();

    $this->user = User::factory()->create();
  }

  public function test_web_index_filters_by_title_partial_match(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->get('/tasks?title=Foo');

    $response->assertOk();
    $response->assertSee('Foo task', false);
    $response->assertDontSee('Bar task', false);
  }

  public function test_web_index_filters_by_status(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->get('/tasks?status=done');

    $response->assertOk();
    $response->assertSee('Bar task', false);
    $response->assertDontSee('Foo task', false);
  }

  public function test_web_index_sorts_due_date_asc_with_nulls_first(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->get('/tasks?due_date_sort=asc');

    $response->assertOk();
    $content = $response->getContent();
    $this->assertNotFalse($content);
    $fooPos = strpos($content, 'Foo task');
    $barPos = strpos($content, 'Bar task');
    $bazPos = strpos($content, 'Baz task');
    $this->assertNotFalse($fooPos);
    $this->assertNotFalse($barPos);
    $this->assertNotFalse($bazPos);
    $this->assertLessThan($barPos, $fooPos);
    $this->assertLessThan($bazPos, $barPos);
  }

  public function test_web_index_sorts_due_date_desc(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->get('/tasks?due_date_sort=desc');

    $response->assertOk();
    $content = $response->getContent();
    $this->assertNotFalse($content);
    $fooPos = strpos($content, 'Foo task');
    $barPos = strpos($content, 'Bar task');
    $bazPos = strpos($content, 'Baz task');
    $this->assertNotFalse($fooPos);
    $this->assertNotFalse($barPos);
    $this->assertNotFalse($bazPos);
    $this->assertLessThan($bazPos, $fooPos);
    $this->assertLessThan($barPos, $bazPos);
  }

  public function test_api_index_filters_by_title_partial_match(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->getJson('/api/tasks?title=Foo');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    $this->assertSame(['Foo task'], $titles);
  }

  public function test_api_index_filters_by_status(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->getJson('/api/tasks?status=done');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    $this->assertSame(['Bar task'], $titles);
  }

  public function test_api_index_sorts_due_date_asc_with_nulls_first(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->getJson('/api/tasks?due_date_sort=asc');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    $this->assertSame(['Foo task', 'Bar task', 'Baz task'], $titles);
  }

  public function test_api_index_sorts_due_date_desc(): void
  {
    $this->seedTasks();

    $response = $this->actingAs($this->user)->getJson('/api/tasks?due_date_sort=desc');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    $this->assertSame(['Foo task', 'Baz task', 'Bar task'], $titles);
  }

  public function test_web_index_filters_by_priority(): void
  {
    $this->seedTasksWithDistinctPriorities();

    $response = $this->actingAs($this->user)->get('/tasks?priority=high');

    $response->assertOk();
    $response->assertSee('Bar task', false);
    $response->assertDontSee('Foo task', false);
    $response->assertDontSee('Baz task', false);
  }

  public function test_web_index_sorts_priority_asc(): void
  {
    $this->seedTasksWithDistinctPriorities();

    $response = $this->actingAs($this->user)->get('/tasks?priority_sort=asc&due_date_sort=asc');

    $response->assertOk();
    $content = $response->getContent();
    $this->assertNotFalse($content);
    $fooPos = strpos($content, 'Foo task');
    $bazPos = strpos($content, 'Baz task');
    $barPos = strpos($content, 'Bar task');
    $this->assertNotFalse($fooPos);
    $this->assertNotFalse($bazPos);
    $this->assertNotFalse($barPos);
    $this->assertLessThan($bazPos, $fooPos);
    $this->assertLessThan($barPos, $bazPos);
  }

  public function test_web_index_sorts_priority_desc(): void
  {
    $this->seedTasksWithDistinctPriorities();

    $response = $this->actingAs($this->user)->get('/tasks?priority_sort=desc&due_date_sort=asc');

    $response->assertOk();
    $content = $response->getContent();
    $this->assertNotFalse($content);
    $fooPos = strpos($content, 'Foo task');
    $bazPos = strpos($content, 'Baz task');
    $barPos = strpos($content, 'Bar task');
    $this->assertNotFalse($fooPos);
    $this->assertNotFalse($bazPos);
    $this->assertNotFalse($barPos);
    $this->assertLessThan($bazPos, $barPos);
    $this->assertLessThan($fooPos, $bazPos);
  }

  public function test_api_index_filters_by_priority(): void
  {
    $this->seedTasksWithDistinctPriorities();

    $response = $this->actingAs($this->user)->getJson('/api/tasks?priority=high');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    $this->assertSame(['Bar task'], $titles);
  }

  public function test_api_index_sorts_priority_asc(): void
  {
    $this->seedTasksWithDistinctPriorities();

    $response = $this->actingAs($this->user)->getJson('/api/tasks?priority_sort=asc&due_date_sort=asc');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    $this->assertSame(['Foo task', 'Baz task', 'Bar task'], $titles);
  }

  public function test_api_index_sorts_priority_desc(): void
  {
    $this->seedTasksWithDistinctPriorities();

    $response = $this->actingAs($this->user)->getJson('/api/tasks?priority_sort=desc&due_date_sort=asc');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    $this->assertSame(['Bar task', 'Baz task', 'Foo task'], $titles);
  }

  private function seedTasks(): void
  {
    Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Foo task',
      'description' => null,
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => null,
    ]);

    Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Bar task',
      'description' => null,
      'status' => 'done',
      'priority' => 'medium',
      'due_date' => '2026-06-01',
    ]);

    Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Baz task',
      'description' => null,
      'status' => 'in_progress',
      'priority' => 'medium',
      'due_date' => '2026-06-15',
    ]);
  }

  private function seedTasksWithDistinctPriorities(): void
  {
    Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Foo task',
      'description' => null,
      'status' => 'todo',
      'priority' => 'low',
      'due_date' => null,
    ]);

    Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Bar task',
      'description' => null,
      'status' => 'done',
      'priority' => 'high',
      'due_date' => null,
    ]);

    Task::query()->create([
      'user_id' => $this->user->id,
      'title' => 'Baz task',
      'description' => null,
      'status' => 'in_progress',
      'priority' => 'medium',
      'due_date' => null,
    ]);
  }
}
