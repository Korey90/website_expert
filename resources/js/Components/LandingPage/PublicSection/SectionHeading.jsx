export default function SectionHeading({ title, subtitle, align = 'center', invert = false, className = '' }) {
    if (!title && !subtitle) {
        return null;
    }

    const alignClass = align === 'left' ? 'text-left items-start' : 'text-center items-center';
    const titleClass = invert ? 'text-white' : 'text-gray-900 dark:text-white';
    const subtitleClass = invert ? 'text-white/75' : 'text-gray-500 dark:text-gray-400';

    return (
        <header className={['mb-6 sm:mb-10 flex flex-col gap-3 sm:gap-4', alignClass, className].join(' ')}>
            {title && (
                <h2 className={['font-display text-2xl font-bold tracking-tight sm:text-3xl md:text-4xl lg:text-5xl', titleClass].join(' ')}>
                    {title}
                </h2>
            )}
            {subtitle && (
                <p className={['max-w-2xl text-base leading-7 sm:text-lg', subtitleClass].join(' ')}>
                    {subtitle}
                </p>
            )}
        </header>
    );
}