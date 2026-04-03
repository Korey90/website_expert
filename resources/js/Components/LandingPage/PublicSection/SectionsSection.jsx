import SectionHeading from '@/Components/LandingPage/PublicSection/SectionHeading';
import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';

const ICONS = {
    check: (
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
    ),
    star: (
        <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </svg>
    ),
    bolt: (
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
        </svg>
    ),
    shield: (
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
    ),
};

export default function SectionsSection({ content = {}, settings = {} }) {
    const items = content.items ?? [];

    return (
        <SectionShell settings={settings} backgroundFallback="muted" width="wide">
            <SectionHeading title={content.headline} align="center" />

            {items.length > 0 ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {items.map((item, index) => (
                        <article
                            key={`${item.title ?? 'feature'}-${index}`}
                            className="group rounded-[1.75rem] border border-black/5 bg-white/85 p-6 shadow-sm backdrop-blur transition-transform duration-200 hover:-translate-y-1 dark:border-white/10 dark:bg-white/5"
                        >
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                                {ICONS[item.icon] ?? ICONS.check}
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 dark:text-white">{item.title}</h3>
                            {item.description && (
                                <p className="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-300">{item.description}</p>
                            )}
                        </article>
                    ))}
                </div>
            ) : (
                <div className="rounded-[1.75rem] border border-dashed border-gray-300 px-6 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No sections configured yet.
                </div>
            )}
        </SectionShell>
    );
}