<x-filament-panels::page>
    @php
        $currencySymbol = match ($record->currency ?? 'GBP') {
            'GBP'   => '£',
            'EUR'   => '€',
            'USD'   => '$',
            default => $record->currency ?? '',
        };

        $statusConfig = match ($record->status) {
            'draft'          => ['label' => 'Draft',          'bg' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',           'icon' => 'heroicon-m-pencil-square'],
            'sent'           => ['label' => 'Sent',           'bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',        'icon' => 'heroicon-m-paper-airplane'],
            'partially_paid' => ['label' => 'Partially Paid', 'bg' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400','icon' => 'heroicon-m-banknotes'],
            'paid'           => ['label' => 'Paid',           'bg' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',    'icon' => 'heroicon-m-check-badge'],
            'overdue'        => ['label' => 'Overdue',        'bg' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',            'icon' => 'heroicon-m-exclamation-triangle'],
            'cancelled'      => ['label' => 'Cancelled',      'bg' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400','icon' => 'heroicon-m-x-circle'],
            default          => ['label' => ucfirst($record->status ?? ''), 'bg' => 'bg-gray-100 text-gray-700', 'icon' => 'heroicon-m-question-mark-circle'],
        };

        $isOverdue = $record->status !== 'paid'
            && $record->status !== 'cancelled'
            && $record->due_date
            && $record->due_date->isPast();

        $total      = (float) ($record->total ?? 0);
        $amountPaid = (float) ($record->amount_paid ?? 0);
        $amountDue  = (float) ($record->amount_due ?? $total);
        $paidPct    = $total > 0 ? min(100, round(($amountPaid / $total) * 100)) : 0;

        $fmt = fn ($amount) => $currencySymbol . number_format((float) ($amount ?? 0), 2);
    @endphp

    {{-- ── Soft-delete warning ────────────────────────────────────────────── --}}
    @if($record->deleted_at)
        <div class="mb-2 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 dark:border-red-800/40 dark:bg-red-900/20">
            <x-heroicon-m-trash class="h-5 w-5 flex-shrink-0 text-red-500" />
            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                This invoice has been deleted on <strong>{{ $record->deleted_at->format('d M Y, H:i') }}</strong>.
                Use the Restore button to recover it.
            </p>
        </div>
    @endif

    {{-- ── Overdue warning ─────────────────────────────────────────────────── --}}
    @if($isOverdue)
        <div class="mb-2 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 dark:border-red-800/40 dark:bg-red-900/20">
            <x-heroicon-m-exclamation-triangle class="h-5 w-5 flex-shrink-0 text-red-500" />
            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                This invoice is <strong>overdue</strong> — due date was
                <strong>{{ $record->due_date->format('d M Y') }}</strong>
                ({{ $record->due_date->diffForHumans() }}).
                Amount still outstanding: <strong>{{ $fmt($amountDue) }}</strong>.
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ════════════════════════════════════════════════════════
             LEFT COLUMN  ·  Summary · Payment · Items · Notes · Terms
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6 xl:col-span-2">

            {{-- ── Invoice Summary ──────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                {{-- header bar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <x-heroicon-m-document-text class="h-5 w-5 text-indigo-500" />
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Invoice</span>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->number }}</h2>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {{ $statusConfig['bg'] }}">
                        <x-dynamic-component :component="$statusConfig['icon']" class="h-4 w-4" />
                        {{ $statusConfig['label'] }}
                    </span>
                </div>

                {{-- key fields grid --}}
                <div class="grid grid-cols-2 gap-x-8 gap-y-5 px-6 py-5 sm:grid-cols-4">

                    {{-- Total --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Total</dt>
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ $fmt($record->total) }}</dd>
                    </div>

                    {{-- Amount Due --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Amount Due</dt>
                        <dd class="text-2xl font-bold {{ $amountDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $fmt($amountDue) }}
                        </dd>
                    </div>

                    {{-- Issue Date --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Issue Date</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $record->issue_date ? $record->issue_date->format('d M Y') : '—' }}
                        </dd>
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Due Date</dt>
                        <dd class="text-sm">
                            @if($record->due_date)
                                <span class="{{ $isOverdue ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $record->due_date->format('d M Y') }}
                                </span>
                                @if($isOverdue)
                                    <span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                        overdue
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-400">Not set</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Sent --}}
                    @if($record->sent_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Sent</dt>
                        <dd class="flex items-center gap-1.5 text-sm text-gray-900 dark:text-white">
                            <x-heroicon-m-paper-airplane class="h-4 w-4 text-blue-400" />
                            {{ $record->sent_at->format('d M Y') }}
                        </dd>
                    </div>
                    @endif

                    {{-- Paid --}}
                    @if($record->paid_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Paid</dt>
                        <dd class="flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
                            <x-heroicon-m-check-badge class="h-4 w-4" />
                            {{ $record->paid_at->format('d M Y') }}
                        </dd>
                    </div>
                    @endif

                    {{-- Currency --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Currency</dt>
                        <dd class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {{ $record->currency }}
                        </dd>
                    </div>

                    {{-- VAT Rate --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">VAT Rate</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ number_format((float) ($record->vat_rate ?? 0), 0) }}%
                        </dd>
                    </div>

                </div>

                {{-- Stripe payment link --}}
                @if($record->stripe_payment_link)
                    <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                        <a href="{{ $record->stripe_payment_link }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 active:bg-indigo-800">
                            <x-heroicon-m-credit-card class="h-4 w-4" />
                            Pay via Stripe
                            <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5 opacity-70" />
                        </a>
                    </div>
                @endif
            </div>

            {{-- ── Payment Progress ─────────────────────────────────── --}}
            @if($record->status !== 'draft' && $record->status !== 'cancelled')
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-banknotes class="h-4 w-4 text-green-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</h2>
                </div>
                <div class="px-6 py-5">
                    {{-- Progress bar --}}
                    <div class="mb-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Paid: <strong class="text-gray-900 dark:text-white">{{ $fmt($amountPaid) }}</strong></span>
                        <span>{{ $paidPct }}% of {{ $fmt($total) }}</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-2.5 rounded-full transition-all
                            {{ $paidPct >= 100 ? 'bg-green-500' : ($paidPct > 0 ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-700') }}"
                             style="width: {{ $paidPct }}%">
                        </div>
                    </div>

                    {{-- Three columns --}}
                    <div class="mt-5 grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-800">
                        <div class="pr-6 text-center">
                            <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Invoice Total</dt>
                            <dd class="text-lg font-bold text-gray-900 dark:text-white">{{ $fmt($total) }}</dd>
                        </div>
                        <div class="px-6 text-center">
                            <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Paid</dt>
                            <dd class="text-lg font-bold text-green-600 dark:text-green-400">{{ $fmt($amountPaid) }}</dd>
                        </div>
                        <div class="pl-6 text-center">
                            <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Outstanding</dt>
                            <dd class="text-lg font-bold {{ $amountDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $fmt($amountDue) }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Line Items ──────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-list-bullet class="h-4 w-4 text-indigo-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Line Items</h2>
                    @if($record->items->count())
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800">
                            {{ $record->items->count() }}
                        </span>
                    @endif
                </div>

                @if($record->items->count())
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Unit Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($record->items as $i => $item)
                                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30">
                                        <td class="px-6 py-4 text-sm text-gray-400 dark:text-gray-600">{{ $i + 1 }}</td>
                                        <td class="px-4 py-4">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->description }}</p>
                                            @if($item->details)
                                                <p class="mt-0.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $item->details }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                            {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}
                                        </td>
                                        <td class="px-4 py-4 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                            {{ $fmt($item->unit_price) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                            {{ $fmt($item->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals block --}}
                    <div class="border-t border-gray-200 bg-gray-50/60 px-6 py-5 dark:border-gray-700 dark:bg-gray-800/30">
                        <div class="ml-auto w-full max-w-xs space-y-2">

                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span class="tabular-nums">{{ $fmt($record->subtotal) }}</span>
                            </div>

                            @if(($record->discount_amount ?? 0) > 0)
                            <div class="flex justify-between text-sm text-red-600 dark:text-red-400">
                                <span>Discount</span>
                                <span class="tabular-nums">− {{ $fmt($record->discount_amount) }}</span>
                            </div>
                            @endif

                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>VAT ({{ number_format((float) ($record->vat_rate ?? 0), 0) }}%)</span>
                                <span class="tabular-nums">{{ $fmt($record->vat_amount) }}</span>
                            </div>

                            <div class="flex justify-between border-t border-gray-300 pt-3 text-base font-bold text-gray-900 dark:border-gray-600 dark:text-white">
                                <span>Total</span>
                                <span class="tabular-nums">{{ $fmt($record->total) }}</span>
                            </div>

                            @if($amountPaid > 0)
                            <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                                <span>Paid</span>
                                <span class="tabular-nums">− {{ $fmt($amountPaid) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2 text-sm font-semibold dark:border-gray-700
                                {{ $amountDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                <span>Amount Due</span>
                                <span class="tabular-nums">{{ $fmt($amountDue) }}</span>
                            </div>
                            @endif

                        </div>
                    </div>

                @else
                    <div class="py-12 text-center">
                        <x-heroicon-o-list-bullet class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                        <p class="text-sm text-gray-400">No line items added.</p>
                    </div>
                @endif
            </div>

            {{-- ── Payment History ─────────────────────────────────── --}}
            @php $payments = $record->payments()->orderBy('paid_at', 'desc')->get(); @endphp
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-banknotes class="h-4 w-4 text-green-500" />
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Payment History
                        </h2>
                        @if($payments->count() > 0)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {{ $payments->count() }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('filament.admin.resources.payments.create') }}?invoice_id={{ $record->id }}"
                       class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-800">
                        + Add payment
                    </a>
                </div>
                @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Method</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reference</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($payments as $payment)
                            <tr>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">
                                    {{ $payment->paid_at ? $payment->paid_at->format('d M Y, H:i') : '—' }}
                                </td>
                                <td class="px-4 py-2.5">
                                    @php $methodMap = ['stripe'=>'Stripe','payu'=>'PayU','bank_transfer'=>'Bank Transfer','cash'=>'Cash','cheque'=>'Cheque','other'=>'Other']; @endphp
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        {{ $methodMap[$payment->method] ?? $payment->method }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    @php
                                    $sc = match($payment->status) {
                                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'pending'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'failed'    => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'refunded'  => 'bg-gray-100 text-gray-600',
                                        default     => 'bg-gray-100 text-gray-600',
                                    }; @endphp
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $payment->reference ?: '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                                    {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="py-8 text-center">
                    <x-heroicon-o-banknotes class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                    <p class="text-sm text-gray-400">No payments recorded.</p>
                </div>
                @endif
            </div>

            {{-- ── Notes ──────────────────────────────────────────── --}}
            @if($record->notes)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-document-text class="h-4 w-4 text-yellow-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</h2>
                </div>
                <div class="px-6 py-5">
                    <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $record->notes }}</p>
                </div>
            </div>
            @endif

            {{-- ── Terms & Conditions ──────────────────────────────── --}}
            @if($record->terms)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-scale class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Terms &amp; Conditions</h2>
                </div>
                <div class="px-6 py-5">
                    <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $record->terms }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- ════════════════════════════════════════════════════════
             RIGHT COLUMN  ·  Client · Project · Quote · Metadata
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6">

            {{-- ── Client ─────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-building-office-2 class="h-4 w-4 text-indigo-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Client</h2>
                </div>
                <div class="px-6 py-5">
                    @if($record->client)
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                                {{ mb_strtoupper(mb_substr($record->client->company_name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $record->client->company_name }}</p>
                                    @if($record->client->deleted_at)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400"
                                              title="Deleted on {{ $record->client->deleted_at->format('d M Y') }}">
                                            <x-heroicon-m-trash class="h-3 w-3" />
                                            Deleted
                                        </span>
                                    @endif
                                </div>
                                @if($record->client->primary_contact_name)
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $record->client->primary_contact_name }}</p>
                                @endif
                                @if($record->client->primary_contact_email)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->client->primary_contact_email }}</p>
                                @endif
                                @if($record->client->primary_contact_phone)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->client->primary_contact_phone }}</p>
                                @endif
                                @if(! $record->client->deleted_at)
                                    <a href="{{ route('filament.admin.resources.clients.view', $record->client_id) }}"
                                       class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                        View Client
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No client linked.</p>
                    @endif
                </div>
            </div>

            {{-- ── Related Project ──────────────────────────────────── --}}
            @if($record->project_id)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-folder-open class="h-4 w-4 text-blue-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Project</h2>
                </div>
                <div class="px-6 py-5">
                    @if($record->project)
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->project->title }}</p>
                        @if($record->project->status)
                            <span class="mt-1.5 inline-block rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                {{ ucfirst(str_replace('_', ' ', $record->project->status)) }}
                            </span>
                        @endif
                        @php $serviceLabels = ['wizytowka' => 'Business Card', 'landing' => 'Landing Page', 'ecommerce' => 'E-Commerce', 'aplikacja' => 'Web Application', 'seo' => 'SEO', 'other' => 'Other']; @endphp
                        @if($record->project->service_type)
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                {{ $serviceLabels[$record->project->service_type] ?? $record->project->service_type }}
                            </p>
                        @endif
                        @if(! $record->project->deleted_at)
                            <a href="{{ route('filament.admin.resources.projects.view', $record->project_id) }}"
                               class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                View Project
                            </a>
                        @endif
                    @else
                        <p class="text-sm text-gray-400">Project #{{ $record->project_id }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Related Quote ────────────────────────────────────── --}}
            @if($record->quote_id)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-clipboard-document-list class="h-4 w-4 text-yellow-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Related Quote</h2>
                </div>
                <div class="px-6 py-5">
                    @if($record->quote)
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->quote->number }}</p>
                        @if($record->quote->status)
                            <span class="mt-1.5 inline-block rounded-md bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                {{ ucfirst($record->quote->status) }}
                            </span>
                        @endif
                        @if(! $record->quote->deleted_at)
                            <a href="{{ route('filament.admin.resources.quotes.view', $record->quote_id) }}"
                               class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                View Quote
                            </a>
                        @endif
                    @else
                        <p class="text-sm text-gray-400">Quote #{{ $record->quote_id }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Metadata ────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-information-circle class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Details</h2>
                </div>
                <div class="space-y-4 px-6 py-5">

                    @if($record->createdBy)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Created By</dt>
                        <dd class="flex items-center gap-1.5 text-sm text-gray-900 dark:text-white">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                                {{ mb_strtoupper(mb_substr($record->createdBy->name, 0, 1)) }}
                            </span>
                            {{ $record->createdBy->name }}
                        </dd>
                    </div>
                    @endif

                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Created</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $record->created_at->format('d M Y, H:i') }}
                            <span class="ml-1 text-xs text-gray-400">{{ $record->created_at->diffForHumans() }}</span>
                        </dd>
                    </div>

                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Last Updated</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->updated_at->format('d M Y, H:i') }}</dd>
                    </div>

                    @if($record->sent_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Sent</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->sent_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif

                    @if($record->paid_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Paid At</dt>
                        <dd class="text-sm font-medium text-green-600 dark:text-green-400">{{ $record->paid_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif

                    @if($record->issue_date)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Issue Date</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->issue_date->format('d M Y') }}</dd>
                    </div>
                    @endif

                    @if($record->due_date)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Due Date</dt>
                        <dd class="text-sm {{ $isOverdue ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $record->due_date->format('d M Y') }}
                        </dd>
                    </div>
                    @endif

                </div>
            </div>

        </div>{{-- /right col --}}

    </div>{{-- /grid --}}

</x-filament-panels::page>
