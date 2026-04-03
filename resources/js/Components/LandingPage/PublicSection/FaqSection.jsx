import { useState } from 'react';
import SectionHeading from '@/Components/LandingPage/PublicSection/SectionHeading';
import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';

function ChevronIcon({ open }) {
    return (
        <svg
            className={`h-5 w-5 text-gray-500 dark:text-gray-400 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={2}
        >
            <path strokeLinecap="round" strokeLinejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    );
}

/**
 * FAQ accordion section — single item open at a time.
 */
export default function FaqSection({ content = {}, settings = {} }) {
    const [openIndex, setOpenIndex] = useState(null);
    const items = content.items ?? [];

    const toggle = (i) => setOpenIndex((prev) => (prev === i ? null : i));

    return (
        <SectionShell settings={settings} backgroundFallback="muted" width="narrow">
            <SectionHeading title={content.headline} align="center" />

                <div className="divide-y divide-gray-200 dark:divide-gray-700 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                    {items.length > 0 ? (
                        items.map((item, i) => (
                            <div key={i}>
                                <button
                                    type="button"
                                    onClick={() => toggle(i)}
                                    className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-inset"
                                    aria-expanded={openIndex === i}
                                >
                                    <span className="font-semibold text-gray-900 dark:text-white text-sm sm:text-base">
                                        {item.question}
                                    </span>
                                    <ChevronIcon open={openIndex === i} />
                                </button>

                                {openIndex === i && (
                                    <div className="px-6 pb-5">
                                        <p className="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                                            {item.answer}
                                        </p>
                                    </div>
                                )}
                            </div>
                        ))
                    ) : (
                        <p className="py-8 text-center text-gray-400 dark:text-gray-500 text-sm italic">No FAQ items yet.</p>
                    )}
                </div>
        </SectionShell>
    );
}
