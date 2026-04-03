import { useState, useRef } from 'react';

/**
 * ServicesList — dynamic tag-style list for adding/removing services.
 * Supports keyboard enter to add, backspace on empty to remove last.
 */
export default function ServicesList({ label, value = [], onChange, placeholder, max = 20, error }) {
    const [inputValue, setInputValue] = useState('');
    const inputRef = useRef(null);

    const addService = () => {
        const trimmed = inputValue.trim();
        if (!trimmed || value.includes(trimmed) || value.length >= max) return;
        onChange([...value, trimmed]);
        setInputValue('');
    };

    const removeService = (index) => {
        onChange(value.filter((_, i) => i !== index));
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addService();
        } else if (e.key === 'Backspace' && inputValue === '' && value.length > 0) {
            removeService(value.length - 1);
        }
    };

    return (
        <div>
            {label && (
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {label}
                </label>
            )}

            {/* Tags container — clicking anywhere focuses the input */}
            <div
                onClick={() => inputRef.current?.focus()}
                className={
                    'min-h-[44px] flex flex-wrap gap-2 items-center rounded-md border px-3 py-2 cursor-text ' +
                    'bg-white dark:bg-gray-800 ' +
                    (error
                        ? 'border-red-500 dark:border-red-400'
                        : 'border-gray-300 dark:border-gray-600 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500')
                }
            >
                {value.map((service, index) => (
                    <span
                        key={index}
                        className="inline-flex items-center gap-1 rounded-full bg-brand-100 dark:bg-brand-900/30 px-2.5 py-0.5 text-xs font-medium text-brand-800 dark:text-brand-300"
                    >
                        {service}
                        <button
                            type="button"
                            onClick={(e) => { e.stopPropagation(); removeService(index); }}
                            className="ml-0.5 text-brand-600 dark:text-brand-400 hover:text-brand-900 dark:hover:text-brand-200 focus:outline-none"
                            aria-label={`Remove ${service}`}
                        >
                            <svg className="h-3 w-3" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M7.414 6l3.293-3.293a1 1 0 0 0-1.414-1.414L6 4.586 2.707 1.293a1 1 0 0 0-1.414 1.414L4.586 6 1.293 9.293a1 1 0 1 0 1.414 1.414L6 7.414l3.293 3.293a1 1 0 0 0 1.414-1.414L7.414 6z" />
                            </svg>
                        </button>
                    </span>
                ))}

                <input
                    ref={inputRef}
                    type="text"
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={handleKeyDown}
                    onBlur={addService}
                    placeholder={value.length === 0 ? (placeholder ?? 'Add a service…') : ''}
                    className="flex-1 min-w-[120px] border-none bg-transparent text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-0 p-0"
                />
            </div>

            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Press <kbd className="font-mono">Enter</kbd> to add · {value.length}/{max} services
            </p>

            {error && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>
            )}
        </div>
    );
}
