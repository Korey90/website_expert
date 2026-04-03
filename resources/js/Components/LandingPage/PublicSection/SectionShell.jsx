const BACKGROUND_CLASSES = {
    white: 'bg-white text-gray-900 dark:bg-gray-950 dark:text-white',
    dark: 'bg-gray-950 text-white dark:bg-black dark:text-white',
    primary: 'bg-brand-600 text-white dark:bg-brand-700 dark:text-white',
    gradient: 'bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.16),transparent_35%),linear-gradient(135deg,#88160c_0%,#ff2b17_55%,#ff8f73_100%)] text-white',
    muted: 'bg-neutral-50 text-gray-900 dark:bg-gray-900 dark:text-white',
};

const PADDING_CLASSES = {
    sm: 'py-14 sm:py-16',
    md: 'py-18 sm:py-20 lg:py-24',
    lg: 'py-24 sm:py-28 lg:py-32',
};

const WIDTH_CLASSES = {
    narrow: 'max-w-3xl',
    content: 'max-w-5xl',
    wide: 'max-w-6xl',
    full: 'max-w-7xl',
};

export default function SectionShell({
    children,
    settings = {},
    backgroundFallback = 'white',
    width = 'content',
    className = '',
    innerClassName = '',
    as: Tag = 'section',
    id,
}) {
    const background = settings.background ?? backgroundFallback;
    const padding = settings.padding ?? 'md';

    return (
        <Tag
            id={id}
            className={[
                BACKGROUND_CLASSES[background] ?? BACKGROUND_CLASSES[backgroundFallback],
                PADDING_CLASSES[padding] ?? PADDING_CLASSES.md,
                className,
            ].join(' ')}
        >
            <div className={[WIDTH_CLASSES[width] ?? WIDTH_CLASSES.content, 'mx-auto px-4 sm:px-6 lg:px-8', innerClassName].join(' ')}>
                {children}
            </div>
        </Tag>
    );
}