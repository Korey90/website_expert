<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        @php
            $columns = [
                'todo'        => ['label' => 'To Do',       'color' => 'zinc'],
                'in_progress' => ['label' => 'In Progress', 'color' => 'blue'],
                'review'      => ['label' => 'Review',      'color' => 'yellow'],
                'done'        => ['label' => 'Done',        'color' => 'green'],
            ];
            $grouped = $this->getGroupedTasks();
        @endphp

        @foreach($columns as $status => $col)
        <div class="flex flex-col rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 overflow-hidden">

            {{-- Column header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full
                        @if($col['color'] === 'zinc') bg-zinc-400
                        @elseif($col['color'] === 'blue') bg-blue-500
                        @elseif($col['color'] === 'yellow') bg-yellow-400
                        @elseif($col['color'] === 'green') bg-green-500
                        @endif
                    "></span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $col['label'] }}</span>
                    <span class="text-xs text-gray-400 bg-gray-200 dark:bg-gray-700 rounded-full px-2 py-0.5 leading-5">
                        {{ count($grouped[$status]) }}
                    </span>
                </div>
            </div>

            {{-- Task cards --}}
            <div class="flex-1 p-3 space-y-2 overflow-y-auto" style="min-height:8rem;max-height:64vh">
                @forelse($grouped[$status] as $task)
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-600 p-3 shadow-sm">

                    {{-- Title --}}
                    <p class="text-sm font-medium text-gray-900 dark:text-white leading-snug mb-2">
                        {{ $task['title'] }}
                    </p>

                    {{-- Priority badge --}}
                    <div class="flex flex-wrap items-center gap-1 mb-2">
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium
                            @if($task['priority'] === 'urgent') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                            @elseif($task['priority'] === 'high') bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300
                            @elseif($task['priority'] === 'medium') bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300
                            @else bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400
                            @endif
                        ">
                            {{ ucfirst($task['priority']) }}
                        </span>

                        @if(!empty($task['due_date']))
                        <span class="text-xs text-gray-400">
                            📅 {{ \Carbon\Carbon::parse($task['due_date'])->format('d M') }}
                        </span>
                        @endif
                    </div>

                    @if(!empty($task['assigned_to']['name']))
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">
                        👤 {{ $task['assigned_to']['name'] }}
                    </p>
                    @endif

                    @if(!empty($task['description']))
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">
                        {{ $task['description'] }}
                    </p>
                    @endif

                    {{-- Move buttons + delete --}}
                    <div class="flex flex-wrap gap-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @foreach($columns as $targetStatus => $targetCol)
                            @if($targetStatus !== $status)
                            <button
                                wire:click="moveTask({{ $task['id'] }}, '{{ $targetStatus }}')"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                title="Move to {{ $targetCol['label'] }}"
                                class="text-xs px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-primary-600 hover:border-primary-400 dark:hover:text-primary-400 transition-colors"
                            >→ {{ $targetCol['label'] }}</button>
                            @endif
                        @endforeach

                        <button
                            wire:click="deleteTask({{ $task['id'] }})"
                            wire:confirm="Delete this task?"
                            wire:loading.attr="disabled"
                            title="Delete task"
                            class="ml-auto text-xs px-1.5 py-0.5 rounded border border-red-200 dark:border-red-800 text-red-400 hover:text-red-600 hover:border-red-400 transition-colors"
                        >🗑</button>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-sm text-gray-400 dark:text-gray-500">
                    No tasks
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</x-filament-panels::page>
