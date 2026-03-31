<x-filament-panels::page>
    @php
        $rows   = $this->getRows();
        $totals = $this->getTotals();
    @endphp

    <div class="space-y-6">

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center shadow-sm">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totals->total_leads }}</div>
                <div class="text-sm text-gray-500 mt-1">Total Leads</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center shadow-sm">
                <div class="text-3xl font-bold text-green-600">{{ $totals->converted }}</div>
                <div class="text-sm text-gray-500 mt-1">Converted (Won)</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center shadow-sm">
                <div class="text-3xl font-bold text-red-500">{{ $totals->lost }}</div>
                <div class="text-sm text-gray-500 mt-1">Lost</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center shadow-sm">
                <div class="text-3xl font-bold text-blue-600">{{ $totals->conversion_rate }}%</div>
                <div class="text-sm text-gray-500 mt-1">Overall Conversion Rate</div>
            </div>
        </div>

        {{-- Per-source breakdown table --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Breakdown by Source</h2>
            </div>

            @if ($rows->isEmpty())
                <div class="px-6 py-10 text-sm text-gray-500 text-center">No lead data available.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Leads</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Converted</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Lost</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">In Progress</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Conversion Rate</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Won Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white capitalize">
                                        {{ str_replace(['_', '-'], ' ', $row->source) }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-700 dark:text-gray-300">
                                        {{ $row->total_leads }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right font-semibold text-green-600">
                                        {{ $row->converted }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right text-red-500">
                                        {{ $row->lost }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-500">
                                        {{ $row->in_progress }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right">
                                        @php
                                            $rate = $row->conversion_rate;
                                            $color = $rate >= 50 ? 'text-green-600' : ($rate >= 25 ? 'text-yellow-600' : 'text-red-500');
                                        @endphp
                                        <span class="{{ $color }} font-semibold">{{ $rate }}%</span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-700 dark:text-gray-300">
                                        £{{ number_format($row->won_value, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800 font-semibold">
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">Totals</td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900 dark:text-white">{{ $totals->total_leads }}</td>
                                <td class="px-6 py-3 text-sm text-right text-green-600">{{ $totals->converted }}</td>
                                <td class="px-6 py-3 text-sm text-right text-red-500">{{ $totals->lost }}</td>
                                <td class="px-6 py-3 text-sm text-right text-gray-500">{{ $totals->in_progress }}</td>
                                <td class="px-6 py-3 text-sm text-right text-blue-600">{{ $totals->conversion_rate }}%</td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900 dark:text-white">£{{ number_format($totals->won_value, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
