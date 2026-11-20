<div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('finance.expenses.fields.net') }}</div>
        <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $subtotal }}</div>
    </div>

    <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('finance.expenses.fields.vat') }}</div>
        <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $vatAmount }}</div>
    </div>

    <div class="rounded-lg bg-primary-50 p-4 ring-1 ring-primary-600/20 dark:bg-primary-500/10 dark:ring-primary-400/20">
        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('finance.expenses.fields.total') }}</div>
        <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $total }}</div>
    </div>
</div>
