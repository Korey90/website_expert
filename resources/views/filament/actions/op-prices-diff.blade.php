@php
    $changedCount = collect($changes)->where('changed', true)->count();
    $changedIds   = collect($changes)->where('changed', true)->pluck('id')->map(fn($id) => (string)$id)->values()->all();
    $fmt = fn (?float $v): string => $v !== null ? '£' . number_format($v, 2) : '—';
    $trend = function (?float $cur, ?float $new): string {
        if ($cur === null || $new === null || $cur == $new) return '';
        return $new > $cur
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="inline w-3.5 h-3.5 text-red-500 dark:text-red-400 ml-0.5 -mt-0.5"><path fill-rule="evenodd" d="M8 14a.75.75 0 0 1-.75-.75V4.56L4.03 7.78a.75.75 0 0 1-1.06-1.06l4.5-4.5a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1-1.06 1.06L8.75 4.56v8.69A.75.75 0 0 1 8 14Z" clip-rule="evenodd" transform="rotate(180 8 8)"/></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="inline w-3.5 h-3.5 text-green-500 dark:text-green-400 ml-0.5 -mt-0.5"><path fill-rule="evenodd" d="M8 14a.75.75 0 0 1-.75-.75V4.56L4.03 7.78a.75.75 0 0 1-1.06-1.06l4.5-4.5a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1-1.06 1.06L8.75 4.56v8.69A.75.75 0 0 1 8 14Z" clip-rule="evenodd"/></svg>';
    };
@endphp

@if(empty($changes))
    <p class="text-sm text-gray-500 dark:text-gray-400">No active TLDs found in the price list.</p>
@else
<div
    x-data="{
        allIds: {{ Illuminate\Support\Js::from($changedIds) }},
        get selected() {
            return ($wire.mountedActions[0]?.data?.selected_tlds ?? []).map(String);
        },
        isChecked(id) {
            return this.selected.includes(String(id));
        },
        toggle(id) {
            let s = [...this.selected];
            id = String(id);
            s.includes(id) ? (s = s.filter(x => x !== id)) : s.push(id);
            $wire.set('mountedActions.0.data.selected_tlds', s);
        },
        selectAll()   { $wire.set('mountedActions.0.data.selected_tlds', [...this.allIds]); },
        deselectAll() { $wire.set('mountedActions.0.data.selected_tlds', []); },
        get selectedCount() { return this.selected.length; },
    }"
    class="overflow-x-auto"
>
    @if($changedCount === 0)
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3">
            <p class="text-sm font-medium text-green-800 dark:text-green-300">
                ✓ All wholesale prices are up to date — no changes detected.
            </p>
        </div>
    @else
        <div class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3 flex items-center justify-between gap-4">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                {{ $changedCount }} TLD{{ $changedCount > 1 ? 's have' : ' has' }} wholesale price changes.
                Retail prices will be recalculated using each TLD's margin %.
            </p>
            <div class="flex items-center gap-3 shrink-0 text-xs">
                <span class="text-amber-700 dark:text-amber-400 tabular-nums">
                    (<span x-text="selectedCount"></span>/{{ $changedCount }} selected)
                </span>
                <button type="button" @click="selectAll()"
                    class="text-amber-700 dark:text-amber-400 underline hover:no-underline">
                    Select all
                </button>
                <button type="button" @click="deselectAll()"
                    class="text-amber-700 dark:text-amber-400 underline hover:no-underline">
                    None
                </button>
            </div>
        </div>
    @endif

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                <th class="pb-2 pr-2 w-8"></th>
                <th class="pb-2 pr-3 text-left">TLD</th>
                <th class="pb-2 pr-3 text-right">Register /yr<br><span class="normal-case font-normal text-gray-400">(retail → new retail)</span></th>
                <th class="pb-2 pr-3 text-right">Renew /yr<br><span class="normal-case font-normal text-gray-400">(retail → new retail)</span></th>
                <th class="pb-2 pr-3 text-right">Transfer<br><span class="normal-case font-normal text-gray-400">(retail → new retail)</span></th>
                <th class="pb-2 pr-3 text-center">Margin</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($changes as $row)
                @php
                    $changed      = $row['changed'];
                    $rowId        = (string) $row['id'];
                    $regChanged   = $row['op_register']  !== $row['cur_register'];
                    $renewChanged = $row['op_renew']     !== $row['cur_renew'];
                    $xfrChanged   = $row['op_transfer']  !== $row['cur_transfer'];
                @endphp
                <tr
                    x-bind:class="{{ $changed ? 'true' : 'false' }} && isChecked('{{ $rowId }}') ? 'bg-amber-50/60 dark:bg-amber-900/10' : ''"
                    class="{{ ! $changed ? 'opacity-50' : '' }}"
                >
                    {{-- Checkbox --}}
                    <td class="py-2 pr-2">
                        @if($changed)
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                :checked="isChecked('{{ $rowId }}')"
                                @change="toggle('{{ $rowId }}')"
                            >
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 text-green-500 dark:text-green-400 mx-auto" title="Up to date">
                                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </td>

                    {{-- TLD --}}
                    <td class="py-2 pr-3 font-mono font-semibold text-sm {{ $changed ? 'text-amber-700 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $row['tld'] }}
                    </td>

                    {{-- Register --}}
                    <td class="py-2 pr-3 text-right tabular-nums">
                        @if($regChanged)
                            <span class="line-through text-gray-400 text-xs mr-1">{{ $fmt($row['cur_retail_register'] ?? null) }}</span>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">{{ $fmt($row['new_retail_register'] ?? null) }}{!! $trend($row['cur_retail_register'] ?? null, $row['new_retail_register'] ?? null) !!}</span>
                            <br><span class="text-gray-400 text-xs">wholesale: {{ $fmt($row['op_register']) }}</span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">{{ $fmt($row['cur_retail_register'] ?? null) }}</span>
                            <br><span class="text-gray-400 text-xs">wholesale: {{ $fmt($row['op_register']) }}</span>
                        @endif
                    </td>

                    {{-- Renew --}}
                    <td class="py-2 pr-3 text-right tabular-nums">
                        @if($renewChanged)
                            <span class="line-through text-gray-400 text-xs mr-1">{{ $fmt($row['cur_retail_renew'] ?? null) }}</span>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">{{ $fmt($row['new_retail_renew'] ?? null) }}{!! $trend($row['cur_retail_renew'] ?? null, $row['new_retail_renew'] ?? null) !!}</span>
                            <br><span class="text-gray-400 text-xs">wholesale: {{ $fmt($row['op_renew']) }}</span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">{{ $fmt($row['cur_retail_renew'] ?? null) }}</span>
                            <br><span class="text-gray-400 text-xs">wholesale: {{ $fmt($row['op_renew']) }}</span>
                        @endif
                    </td>

                    {{-- Transfer --}}
                    <td class="py-2 pr-3 text-right tabular-nums">
                        @if($xfrChanged)
                            <span class="line-through text-gray-400 text-xs mr-1">{{ $fmt($row['cur_retail_transfer'] ?? null) }}</span>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">{{ $fmt($row['new_retail_transfer'] ?? null) }}{!! $trend($row['cur_retail_transfer'] ?? null, $row['new_retail_transfer'] ?? null) !!}</span>
                            <br><span class="text-gray-400 text-xs">wholesale: {{ $fmt($row['op_transfer']) }}</span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">{{ $fmt($row['cur_retail_transfer'] ?? null) }}</span>
                            <br><span class="text-gray-400 text-xs">wholesale: {{ $fmt($row['op_transfer']) }}</span>
                        @endif
                    </td>

                    <td class="py-2 pr-3 text-center text-gray-500 dark:text-gray-400 tabular-nums">
                        {{ $row['margin_percent'] }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
        Retail = wholesale × (1 + margin%). Strikethrough = current price charged to customers. Bold = new price after sync.
    </p>
</div>
@endif
