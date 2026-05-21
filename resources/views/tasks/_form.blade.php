@php
    /** @var \App\Models\Task|null $task */
    $task = $task ?? null;
@endphp

<div class="space-y-1">
    <div class="app-form-field">
        <x-input-label for="title" value="タイトル" />
        <x-text-input
            id="title"
            type="text"
            name="title"
            :value="old('title', $task?->title ?? '')"
            required
            class="block w-full"
        />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="app-form-field">
        <x-input-label for="description" value="説明" />
        <x-textarea-input
            id="description"
            name="description"
            rows="4"
            class="block w-full"
        >{{ old('description', $task?->description ?? '') }}</x-textarea-input>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="app-form-field">
        <x-input-label for="status" value="ステータス" />
        <x-select-input id="status" name="status" required class="block w-full">
            @foreach (config('task.status_values') as $status)
                <option value="{{ $status }}" @selected(old('status', $task?->status ?? '') === $status)>
                    {{ $status }}
                </option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div class="app-form-field">
        <x-input-label for="priority" value="優先度" />
        <x-select-input id="priority" name="priority" required class="block w-full">
            @foreach (config('task.priority_values') as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $task?->priority ?? 'medium') === $priority)>
                    {{ $priority }}
                </option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
    </div>

    <div class="app-form-field">
        <x-input-label for="due_date" value="期限" />
        <x-text-input
            id="due_date"
            type="date"
            name="due_date"
            :value="old('due_date', $task?->due_date?->format('Y-m-d') ?? '')"
            class="block w-full"
        />
        <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
    </div>
</div>
