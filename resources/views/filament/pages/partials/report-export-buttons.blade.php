<div class="flex flex-wrap items-center justify-end gap-2">
    @foreach (['csv', 'xlsx', 'pdf'] as $format)
        @if ($url = $this->getExportUrl($format))
            <x-filament::button
                tag="a"
                :href="$url"
                size="sm"
                :color="match ($format) { 'xlsx' => 'success', 'pdf' => 'danger', default => 'gray' }"
                :icon="match ($format) { 'xlsx' => 'heroicon-o-document-chart-bar', 'pdf' => 'heroicon-o-document-text', default => 'heroicon-o-table-cells' }"
            >
                {{ __('finance.exports.formats.'.$format) }}
            </x-filament::button>
        @endif
    @endforeach
</div>
