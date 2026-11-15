<x-filament-panels::page>
    <div class="admin-operator-page">
        <x-filament::section class="admin-operator-filter-card">
            <x-slot name="heading">{{ __('admin.migration.administration.log_viewer.filters_heading') }}</x-slot>
            <x-slot name="description">{{ __('admin.migration.administration.log_viewer.filters_description') }}</x-slot>

            <div class="admin-operator-filters">
                <label class="admin-operator-field">
                    <span>{{ __('admin.migration.administration.log_viewer.file') }}</span>
                    <select wire:model.live="selectedFile" class="admin-operator-control">
                        @forelse($this->logFiles() as $file)
                            <option value="{{ $file }}">{{ $file }}</option>
                        @empty
                            <option value="">{{ __('admin.migration.administration.log_viewer.no_files') }}</option>
                        @endforelse
                    </select>
                </label>

                <label class="admin-operator-field admin-operator-field--compact">
                    <span>{{ __('admin.migration.administration.log_viewer.level') }}</span>
                    <select wire:model.live="levelFilter" class="admin-operator-control">
                        @foreach($this->levels() as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="admin-operator-field">
                    <span>{{ __('admin.migration.administration.log_viewer.search') }}</span>
                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ __('admin.migration.administration.log_viewer.search_placeholder') }}"
                        class="admin-operator-control"
                    />
                </label>
            </div>
        </x-filament::section>

        @php
            $data = $this->paginatedEntries();
        @endphp

        <x-filament::section>
            <x-slot name="heading">{{ __('admin.migration.administration.log_viewer.entries') }}</x-slot>
            <x-slot name="description">
                {{ trans_choice('admin.migration.administration.log_viewer.entries_count', $data['total'], ['count' => number_format($data['total'])]) }}
            </x-slot>

            @if(count($data['items']) === 0)
                <div class="admin-operator-empty">
                    <x-heroicon-o-document-magnifying-glass class="admin-operator-empty-icon" />
                    <p class="admin-operator-empty-title">{{ __('admin.migration.administration.log_viewer.empty_heading') }}</p>
                    <p class="admin-operator-empty-description">{{ __('admin.migration.administration.log_viewer.empty_description') }}</p>
                </div>
            @else
                <div
                    class="admin-operator-table-scroll"
                    tabindex="0"
                    role="region"
                    aria-label="{{ __('admin.migration.administration.log_viewer.table_label') }}"
                >
                    <table class="admin-operator-table admin-operator-table--logs">
                        <thead>
                            <tr>
                                <th>{{ __('admin.migration.administration.log_viewer.datetime') }}</th>
                                <th>{{ __('admin.migration.administration.log_viewer.level') }}</th>
                                <th>{{ __('admin.migration.administration.log_viewer.message') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['items'] as $entry)
                                <tr>
                                    <td class="admin-operator-mono admin-operator-nowrap">{{ $entry['datetime'] ?: '—' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match(strtoupper($entry['level'])) {
                                                'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'admin-operator-badge--danger',
                                                'WARNING' => 'admin-operator-badge--warning',
                                                'NOTICE', 'INFO' => 'admin-operator-badge--info',
                                                default => 'admin-operator-badge--neutral',
                                            };
                                        @endphp
                                        <span class="admin-operator-badge {{ $badgeClass }}">{{ $entry['level'] }}</span>
                                    </td>
                                    <td><pre class="admin-operator-log-message">{{ $entry['message'] }}</pre></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($data['totalPages'] > 1)
                    <nav class="admin-operator-pagination" aria-label="{{ __('admin.migration.administration.log_viewer.pagination_label') }}">
                        <span>
                            {{ __('admin.migration.administration.log_viewer.page_status', [
                                'page' => $data['page'],
                                'pages' => $data['totalPages'],
                                'count' => number_format($data['total']),
                            ]) }}
                        </span>
                        <div>
                            <button type="button" wire:click="previousPage" @disabled($data['page'] <= 1) class="admin-operator-button">
                                {{ __('admin.migration.administration.log_viewer.previous') }}
                            </button>
                            <button type="button" wire:click="nextPage" @disabled($data['page'] >= $data['totalPages']) class="admin-operator-button">
                                {{ __('admin.migration.administration.log_viewer.next') }}
                            </button>
                        </div>
                    </nav>
                @endif
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
