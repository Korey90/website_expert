import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-md border-gray-300 bg-white text-neutral-900 shadow-sm ' +
                'focus:border-red-500 focus:ring-red-500 ' +
                'dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder-neutral-400 ' +
                'dark:focus:border-red-500 dark:focus:ring-red-500 ' +
                className
            }
            ref={localRef}
        />
    );
});
