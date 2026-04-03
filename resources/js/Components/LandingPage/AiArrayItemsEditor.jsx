function renderFieldInput(field, value, onChange) {
    if (field.type === 'textarea') {
        return (
            <textarea
                rows={field.rows ?? 3}
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
            />
        );
    }

    return (
        <input
            type={field.type ?? 'text'}
            min={field.min}
            max={field.max}
            value={value ?? ''}
            onChange={(event) => onChange(field.type === 'number' ? Number(event.target.value) : event.target.value)}
            className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
        />
    );
}

export default function AiArrayItemsEditor({ items, fields, onChange, createItem, t }) {
    const safeItems = Array.isArray(items) ? items : [];

    const updateItem = (itemIndex, key, value) => {
        onChange(safeItems.map((item, index) => (
            index === itemIndex ? { ...item, [key]: value } : item
        )));
    };

    const removeItem = (itemIndex) => {
        onChange(safeItems.filter((_, index) => index !== itemIndex));
    };

    const addItem = () => {
        onChange([...safeItems, createItem()]);
    };

    return (
        <div className="space-y-3">
            {safeItems.length > 0 ? safeItems.map((item, itemIndex) => (
                <div key={`${itemIndex}-${fields[0]?.key ?? 'item'}`} className="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/80">
                    <div className="grid gap-4 lg:grid-cols-2">
                        {fields.map((field) => (
                            <label key={field.key} className={field.fullWidth ? 'lg:col-span-2' : ''}>
                                <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">
                                    {field.label}
                                </span>
                                {renderFieldInput(field, item[field.key], (nextValue) => updateItem(itemIndex, field.key, nextValue))}
                            </label>
                        ))}
                    </div>
                    <button
                        type="button"
                        onClick={() => removeItem(itemIndex)}
                        className="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-red-500 transition hover:text-red-600"
                    >
                        {t('ai.ui.remove_item')}
                    </button>
                </div>
            )) : (
                <div className="rounded-2xl border border-dashed border-neutral-300 px-4 py-5 text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                    {t('ai.ui.no_items')}
                </div>
            )}

            <button
                type="button"
                onClick={addItem}
                className="inline-flex items-center gap-2 rounded-full border border-neutral-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-700 transition hover:border-brand-400 hover:text-brand-600 dark:border-neutral-700 dark:text-neutral-200 dark:hover:border-brand-700 dark:hover:text-brand-300"
            >
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {t('ai.ui.add_item')}
            </button>
        </div>
    );
}