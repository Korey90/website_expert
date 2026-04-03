import SectionHeading from '@/Components/LandingPage/PublicSection/SectionHeading';
import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';

function StarRating({ rating = 5 }) {
    return (
        <div className="flex gap-0.5" aria-label={`${rating} out of 5`}>
            {[1, 2, 3, 4, 5].map((n) => (
                <svg
                    key={n}
                    className={`h-4 w-4 ${n <= rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'}`}
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
            ))}
        </div>
    );
}

/**
 * Testimonials section — card grid with star ratings.
 */
export default function TestimonialsSection({ content = {}, settings = {} }) {
    const items = content.items ?? [];

    return (
        <SectionShell settings={settings} backgroundFallback="white" width="wide">
            <SectionHeading title={content.headline} align="center" />

                {items.length > 0 ? (
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {items.map((item, i) => (
                            <blockquote
                                key={i}
                                className="flex h-full flex-col gap-4 rounded-[1.75rem] border border-gray-100 bg-gray-50 p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900/80"
                            >
                                <StarRating rating={item.rating ?? 5} />
                                <p className="flex-1 text-gray-600 dark:text-gray-300 text-sm leading-relaxed italic">
                                    &ldquo;{item.text}&rdquo;
                                </p>
                                <footer className="flex items-center gap-3">
                                    {item.avatar_path ? (
                                        <img
                                            src={item.avatar_path}
                                            alt={item.author}
                                            className="h-9 w-9 rounded-full object-cover"
                                        />
                                    ) : (
                                        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 text-sm font-bold">
                                            {(item.author ?? '?')[0].toUpperCase()}
                                        </span>
                                    )}
                                    <div>
                                        <div className="text-sm font-semibold text-gray-900 dark:text-white">{item.author}</div>
                                        {item.company && (
                                            <div className="text-xs text-gray-500 dark:text-gray-400">{item.company}</div>
                                        )}
                                    </div>
                                </footer>
                            </blockquote>
                        ))}
                    </div>
                ) : (
                    <p className="text-center text-gray-400 dark:text-gray-500 italic">No testimonials yet.</p>
                )}
        </SectionShell>
    );
}
