<x-app-layout>
  <x-app-container>
    <x-page-heading title="タスク一覧">
      <x-slot name="actions">
        <a href="{{ route('tasks.create') }}" class="app-btn--primary">新規作成</a>
      </x-slot>
    </x-page-heading>

    @if (session('status'))
      <x-flash-message>{{ session('status') }}</x-flash-message>
    @endif

    <x-card>
      <form method="get" action="{{ route('tasks.index') }}" class="flex flex-wrap items-end gap-4">
        <div class="app-form-field mb-0 min-w-[10rem] flex-1">
          <x-input-label for="filter-title" value="タイトル" />
          <x-text-input
            id="filter-title"
            type="search"
            name="title"
            :value="old('title', request('title'))"
            class="block w-full"
          />
          <x-input-error :messages="$errors->get('title')" class="mt-1" />
        </div>

        <div class="app-form-field mb-0 min-w-[8rem] flex-1">
          <x-input-label for="filter-status" value="ステータス" />
          <x-select-input id="filter-status" name="status" class="block w-full">
            <option value="">すべて</option>
            @foreach (config('task.status_values') as $status)
              <option value="{{ $status }}" @selected(old('status', request('status')) === $status)>
                {{ $status }}
              </option>
            @endforeach
          </x-select-input>
          <x-input-error :messages="$errors->get('status')" class="mt-1" />
        </div>

        <div class="app-form-field mb-0 min-w-[8rem] flex-1">
          <x-input-label for="filter-priority" value="優先度" />
          <x-select-input id="filter-priority" name="priority" class="block w-full">
            <option value="">すべて</option>
            @foreach (config('task.priority_values') as $priority)
              <option value="{{ $priority }}" @selected(old('priority', request('priority')) === $priority)>
                {{ $priority }}
              </option>
            @endforeach
          </x-select-input>
          <x-input-error :messages="$errors->get('priority')" class="mt-1" />
        </div>

        <div class="app-form-field mb-0 min-w-[10rem] flex-1">
          <x-input-label value="優先度並び替え" />
          <div class="app-radio-group">
            <label class="app-radio-label">
              <input
                type="radio"
                name="priority_sort"
                value="asc"
                class="app-radio"
                @checked(old('priority_sort', request('priority_sort', 'asc')) === 'asc')
              >
              昇順
            </label>
            <label class="app-radio-label">
              <input
                type="radio"
                name="priority_sort"
                value="desc"
                class="app-radio"
                @checked(old('priority_sort', request('priority_sort')) === 'desc')
              >
              降順
            </label>
          </div>
          <x-input-error :messages="$errors->get('priority_sort')" class="mt-1" />
        </div>

        <div class="app-form-field mb-0 min-w-[10rem] flex-1">
          <x-input-label value="期限並び替え" />
          <div class="app-radio-group">
            <label class="app-radio-label">
              <input
                type="radio"
                name="due_date_sort"
                value="asc"
                class="app-radio"
                @checked(old('due_date_sort', request('due_date_sort', 'asc')) === 'asc')
              >
              昇順
            </label>
            <label class="app-radio-label">
              <input
                type="radio"
                name="due_date_sort"
                value="desc"
                class="app-radio"
                @checked(old('due_date_sort', request('due_date_sort')) === 'desc')
              >
              降順
            </label>
          </div>
          <x-input-error :messages="$errors->get('due_date_sort')" class="mt-1" />
        </div>

        <x-primary-button type="submit">適用</x-primary-button>
      </form>
    </x-card>

    <x-card class="overflow-x-auto p-0">
      <table class="app-table">
        <thead>
          <tr>
            <th>タイトル</th>
            <th>ステータス</th>
            <th>優先度</th>
            <th>期限</th>
            <th class="text-right">操作</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($tasks as $task)
            <tr>
              <td class="font-medium text-gray-900">{{ $task->title }}</td>
              <td>{{ $task->status }}</td>
              <td>{{ $task->priority }}</td>
              <td>{{ $task->due_date?->format('Y-m-d') ?? '-' }}</td>
              <td class="text-right">
                <a href="{{ route('tasks.edit', $task->id) }}" class="app-link me-3">編集</a>
                <form
                  method="post"
                  action="{{ route('tasks.destroy', $task->id) }}"
                  class="inline"
                  onsubmit="return confirm('このタスクを削除しますか？');"
                >
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="app-link--danger">削除</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="py-8 text-center text-gray-500">タスクがありません</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </x-card>
  </x-app-container>
</x-app-layout>
