const FOOTER_LINKS = {
    'Usługi': [
        { label: 'Strony wizytówkowe', href: '#oferta' },
        { label: 'Sklepy e-commerce',  href: '#oferta' },
        { label: 'SEO',                href: '#oferta' },
        { label: 'Google Ads',         href: '#oferta' },
        { label: 'Hosting WWW',        href: '#oferta' },
    ],
    'Firma': [
        { label: 'O nas',              href: '#o-nas' },
        { label: 'Portfolio',          href: '#portfolio' },
        { label: 'Kalkulator kosztów', href: '#kalkulator' },
        { label: 'Kontakt',            href: '#kontakt' },
    ],
    'Prawne': [
        { label: 'Polityka prywatności', href: '#' },
        { label: 'Regulamin',            href: '#' },
        { label: 'Cookies',              href: '#' },
    ],
};

export default function Footer() {
    return (
        <footer className="bg-neutral-950 text-neutral-400 pt-16 pb-8">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">

                    {/* Brand */}
                    <div className="col-span-2 md:col-span-1">
                        <a href="#hero" className="flex items-center gap-2 mb-4" aria-label="Website Expert">
                            <svg width="32" height="32" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                                <rect width="36" height="36" rx="8" className="fill-brand-500" />
                                <path d="M13 18L16.5 21.5L23 14.5" stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <span className="font-display font-bold text-lg text-white">Website<span className="text-brand-500">Expert</span></span>
                        </a>
                        <p className="text-sm leading-relaxed">
                            Tworzymy strony i aplikacje internetowe, które pracują na Twój biznes.
                        </p>
                        <div className="flex gap-3 mt-5">
                            {[
                                { label: 'LinkedIn', path: <><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z" /><circle cx="4" cy="4" r="2" /></> },
                                { label: 'Facebook', path: <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" /> },
                                { label: 'Instagram', path: <><rect x="2" y="2" width="20" height="20" rx="5" ry="5" /><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" /><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" /></>, stroke: true },
                            ].map(s => (
                                <a key={s.label} href="#" aria-label={s.label} className="w-9 h-9 rounded-lg bg-neutral-800 flex items-center justify-center hover:bg-brand-500 transition-colors">
                                    <svg className="w-4 h-4" fill={s.stroke ? 'none' : 'currentColor'} stroke={s.stroke ? 'currentColor' : undefined} strokeWidth={s.stroke ? 2 : undefined} viewBox="0 0 24 24" aria-hidden="true">
                                        {s.path}
                                    </svg>
                                </a>
                            ))}
                        </div>
                    </div>

                    {/* Links */}
                    {Object.entries(FOOTER_LINKS).map(([title, links]) => (
                        <div key={title}>
                            <h3 className="text-sm font-semibold text-white mb-4">{title}</h3>
                            <ul className="space-y-2.5 text-sm">
                                {links.map(l => (
                                    <li key={l.label}>
                                        <a href={l.href} className="hover:text-brand-400 transition-colors">{l.label}</a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="border-t border-neutral-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-neutral-500">
                    <p>&copy; {new Date().getFullYear()} WebsiteExpert. Wszelkie prawa zastrzeżone.</p>
                    <p>Zaprojektowane i zbudowane z ❤️ w Polsce</p>
                </div>
            </div>
        </footer>
    );
}
