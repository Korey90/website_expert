import { useRef } from 'react';

/**
 * ColorPicker — hex color input with live swatch preview.
 * Clicking the swatch opens native <input type="color"> picker.
 */
export default function ColorPicker({ label, value = '#3b82f6', onChange, error }) {
    const colorInputRef = useRef(null);

    const isValidHex = (hex) => /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(hex);

    const handleTextChange = (e) => {
        onChange(e.target.value);
    };

    const handleColorPicker = (e) => {
        onChange(e.target.value);
    };

    return (
        <div>
            {label && (
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {label}
                </label>
            )}
            <div className="flex items-center gap-2">
                {/* Swatch — opens native color picker */}
                <button
                    type="button"
                    onClick={() => colorInputRef.current?.click()}
                    className="h-9 w-9 shrink-0 rounded-md border border-gray-300 dark:border-gray-600 shadow-sm cursor-pointer transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-brand-500"
                    style={{ backgroundColor: isValidHex(value) ? value : '#3b82f6' }}
                    aria-label="Pick colour"
                />
                <input
                    ref={colorInputRef}
                    type="color"
                    value={isValidHex(value) ? value : '#3b82f6'}
                    onChange={handleColorPicker}
                    className="sr-only"
                    tabIndex={-1}
                    aria-hidden="true"
                />

                {/* Text input for manual hex entry */}
                <input
                    type="text"
                    value={value}
                    onChange={handleTextChange}
                    maxLength={7}
                    placeholder="#ff2b17"
                    className={
                        'block w-full rounded-md border shadow-sm text-sm font-mono py-2 px-3 ' +
                        'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 ' +
                        'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 ' +
                        (error
                            ? 'border-red-500 dark:border-red-400'
                            : 'border-gray-300 dark:border-gray-600')
                    }
                />
            </div>
            {error && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>
            )}
        </div>
    );
}
