import { useState, useEffect, useCallback } from 'react';

const TESTIMONIALS = [
    { id: 1, name: 'Anna Kowalska',   role: 'CEO, ModaBoutique',          avatar: 'AK', avatarColor: 'bg-brand-500',   rating: 5, quote: 'Sklep wdrożony w 4 tygodnie. Konwersja wzrosła o 34% w pierwszym kwartale. Polecam bez wahania.' },
    { id: 2, name: 'Marek Błaszczyk', role: 'Właściciel, Kancelaria MB',  avatar: 'MB', avatarColor: 'bg-neutral-600', rating: 5, quote: 'Profesjonalizm na każdym etapie. Strona robi świetne pierwsze wrażenie na moich klientach.' },
    { id: 3, name: 'Piotr Wróbel',    role: 'CTO, FleetTrack',            avatar: 'PW', avatarColor: 'bg-brand-700',   rating: 5, quote: 'Aplikacja webowa z mapami i raportowaniem – dostarczona zgodnie z harmonogramem. Jakość kodu na wysokim poziomie.' },
    { id: 4, name: 'Katarzyna Nowak', role: 'Marketing Manager, FitLife', avatar: 'KN', avatarColor: 'bg-neutral-700', rating: 5, quote: 'Kampania Google Ads przy współpracy z WebsiteExpert dała nam 3× więcej leadów niż poprzednia agencja.' },
    { id: 5, name: 'Tomasz Lewicki',  role: 'Founder, EduStart',          avatar: 'TL', avatarColor: 'bg-brand-600',   rating: 5, quote: 'SEO zaczęło działać po 2 miesiącach – jesteśmy w top 5 Google na kluczowe frazy. Rewelacyjna robota.' },
    { id: 6, name: 'Marta Zielińska', role: 'Dyrektor, KlinikaDent',      avatar: 'MZ', avatarColor: 'bg-neutral-500', rating: 5, quote: 'Responsywna wizytówka z systemem rezerwacji online. Pacjenci chwalą wygodę, a my mamy mniej telefonów.' },
];

function Stars({ count }) {
    return (
        <div className="flex gap-0.5" aria-label={`${count} gwiazdki`}>
            {Array.from({ length: 5 }, (_, i) => (
                <svg key={i} className={`w-4 h-4 ${i < count ? 'text-yellow-400' : 'text-neutral-300 dark:text-neutral-700'}`} fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

export default function TrustStrip() {
    const [current, setCurrent] = useState(0);
    const [isPaused, setIsPaused] = useState(false);
    const total = TESTIMONIALS.length;
    const goTo = useCallback((i) => setCurrent(((i % total) + total) % total), [total]);

    useEffect(() => {
        if (isPaused) return;
        const id = setInterval(() => goTo(current + 1), 5000);
        return () => clearInterval(id);
    }, [current, isPaused, goTo]);

    return (
        <section id="zaufali" className="py-20 flex items-center h-screen md:py-28 bg-neutral-50 dark:bg-neutral-900">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-14 reveal">
                    <span className="section-label">Zaufali nam</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        Firmy, które wybrały WebsiteExpert
                    </h2>
                </div>

                {/* Logo strip */}
                <div className="flex flex-wrap justify-center items-center gap-8 md:gap-12 mb-16 reveal">
                    {['Klient A', 'Klient B', 'Klient C', 'Klient D', 'Klient E'].map(c => (
                        <div key={c} className="h-10 w-28 rounded-lg bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center text-xs text-neutral-400 font-medium">
                            {c}
                        </div>
                    ))}
                </div>

                {/* Testimonial carousel */}
                <div
                    className="max-w-2xl mx-auto"
                    tabIndex={0}
                    role="region"
                    aria-label="Karuzela opinii klientów"
                    onMouseEnter={() => setIsPaused(true)}
                    onMouseLeave={() => setIsPaused(false)}
                    onKeyDown={(e) => {
                        if (e.key === 'ArrowLeft')  goTo(current - 1);
                        if (e.key === 'ArrowRight') goTo(current + 1);
                    }}
                >
                    {/* Sliding cards */}
                    <div className="overflow-hidden" aria-live="polite">
                        <div
                            className="flex transition-transform duration-500 ease-in-out"
                            style={{ transform: `translateX(-${current * 100}%)` }}
                        >
                            {TESTIMONIALS.map((item, idx) => (
                                <div
                                    key={item.id}
                                    className="w-full flex-shrink-0 px-2"
                                    aria-hidden={idx !== current ? 'true' : undefined}
                                >
                                    <article
                                        className="bg-white dark:bg-neutral-950 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-8 md:p-10 shadow-sm"
                                        aria-label={`Opinia od ${item.name}`}
                                    >
                                        <div className="mb-3">
                                            <Stars count={item.rating} />
                                        </div>
                                        <blockquote className="text-neutral-700 dark:text-neutral-300 text-lg leading-relaxed mb-6">
                                            <p>"{item.quote}"</p>
                                        </blockquote>
                                        <div className="flex items-center gap-3">
                                            <div className={`w-10 h-10 rounded-full ${item.avatarColor} flex items-center justify-center text-white text-sm font-bold shrink-0`} aria-hidden="true">
                                                {item.avatar}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-neutral-900 dark:text-white text-sm">{item.name}</p>
                                                <p className="text-xs text-neutral-500 dark:text-neutral-400">{item.role}</p>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Navigation: prev · dots · next */}
                    <div className="flex items-center justify-center gap-4 mt-6">
                        <button
                            type="button"
                            onClick={() => goTo(current - 1)}
                            aria-label="Poprzednia opinia"
                            className="w-9 h-9 rounded-full border border-neutral-200 dark:border-neutral-700 flex items-center justify-center text-neutral-500 hover:border-brand-500 hover:text-brand-500 transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <div className="flex gap-1.5" role="tablist">
                            {TESTIMONIALS.map((_, i) => (
                                <button
                                    key={i}
                                    role="tab"
                                    aria-selected={i === current}
                                    aria-label={`Opinia ${i + 1}`}
                                    onClick={() => goTo(i)}
                                    className={`h-2 rounded-full transition-all duration-300 ${i === current ? 'bg-brand-500 w-5' : 'w-2 bg-neutral-300 dark:bg-neutral-600'}`}
                                />
                            ))}
                        </div>

                        <button
                            type="button"
                            onClick={() => goTo(current + 1)}
                            aria-label="Następna opinia"
                            className="w-9 h-9 rounded-full border border-neutral-200 dark:border-neutral-700 flex items-center justify-center text-neutral-500 hover:border-brand-500 hover:text-brand-500 transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    );
}
