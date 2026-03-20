import { usePage } from '@inertiajs/react';

const SOCIAL_ICONS = {
    linkedin: (
        <>
            <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z" />
            <circle cx="4" cy="4" r="2" />
        </>
    ),
    facebook: <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />,
    instagram: (
        <>
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
            <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" />
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
        </>
    ),
};
const SOCIAL_STROKE = new Set(['instagram']);

const NAV_DEFAULTS = [
    {
        title_en: 'Services', title_pl: 'Usługi',
        links: [
            { label_en: 'Brochure Websites', label_pl: 'Strony wizytówkowe', href: '#oferta' },
            { label_en: 'E-Commerce Stores', label_pl: 'Sklepy e-commerce',  href: '#oferta' },
            { label_en: 'SEO',               label_pl: 'SEO',                href: '#oferta' },
            { label_en: 'Google Ads',        label_pl: 'Google Ads',         href: '#oferta' },
            { label_en: 'Web Hosting',       label_pl: 'Hosting WWW',        href: '#oferta' },
        ],
    },
    {
        title_en: 'Company', title_pl: 'Firma',
        links: [
            { label_en: 'About Us',        label_pl: 'O nas',              href: '#o-nas' },
            { label_en: 'Portfolio',       label_pl: 'Portfolio',          href: '#portfolio' },
            { label_en: 'Cost Calculator', label_pl: 'Kalkulator kosztów', href: '#kalkulator' },
            { label_en: 'Contact',         label_pl: 'Kontakt',            href: '#kontakt' },
            { label_en: 'Client Portal',   label_pl: 'Portal Klienta',     href: '/portal' },
        ],
    },
    {
        title_en: 'Legal', title_pl: 'Prawne',
        links: [
            { label_en: 'Privacy Policy', label_pl: 'Polityka prywatności', href: '#' },
            { label_en: 'Terms of Use',   label_pl: 'Regulamin',            href: '#' },
            { label_en: 'Cookies',        label_pl: 'Cookies',              href: '#' },
        ],
    },
];

const SOCIAL_DEFAULTS = [
    { key: 'linkedin',  url: '#', label: 'LinkedIn'  },
    { key: 'facebook',  url: '#', label: 'Facebook'  },
    { key: 'instagram', url: '#', label: 'Instagram' },
];

export default function Footer({ data = null }) {
    const { locale = 'en' } = usePage().props;
    const extra = data?.extra ?? {};
    const t = (key, fallback = '') => extra[`${key}_${locale}`] ?? extra[`${key}_en`] ?? fallback;

    const brandName   = extra.brand_name   || 'WebsiteExpert';
    const tagline     = t('tagline',    locale === 'pl' ? 'Tworzymy strony i aplikacje internetowe, które pracują na Twój biznes.' : 'We create websites and web apps that work for your business.');
    const copyright   = t('copyright',  locale === 'pl' ? 'Wszelkie prawa zastrzeżone.' : 'All rights reserved.');
    const builtWith   = t('built_with', locale === 'pl' ? 'Zaprojektowane i zbudowane z ❤️ w Polsce' : 'Designed and built with ❤️ in Poland');
    const navGroups   = extra.nav_groups   || NAV_DEFAULTS;
    const socialLinks = extra.social       || SOCIAL_DEFAULTS;

    // Split brand name at 'Expert' for styling: prefix gets neutral, 'Expert' gets brand color
    const expertIdx   = brandName.indexOf('Expert');
    const brandPrefix = expertIdx >= 0 ? brandName.slice(0, expertIdx) : brandName;
    const brandSuffix = expertIdx >= 0 ? 'Expert'                       : '';

    return (
        <footer className="bg-neutral-950 text-neutral-400 pt-16 pb-8">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">

                    {/* Brand */}
                    <div className="col-span-2 md:col-span-1">
                        <a href="#hero" className="flex items-center gap-2 mb-4" aria-label={brandName}>
                            <svg width="32" height="32" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                                <rect width="36" height="36" rx="8" className="fill-brand-500" />
                                <path d="M13 18L16.5 21.5L23 14.5" stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            <span className="font-display font-bold text-lg text-white">
                                {brandPrefix}<span className="text-brand-500">{brandSuffix}</span>
                            </span>
                        </a>
                        <p className="text-sm leading-relaxed">{tagline}</p>
                        <div className="flex gap-3 mt-5">
                            {socialLinks.map(s => {
                                const icon     = SOCIAL_ICONS[s.key];
                                const isStroke = SOCIAL_STROKE.has(s.key);
                                if (!icon) return null;
                                return (
                                    <a key={s.key} href={s.url || '#'} aria-label={s.label}
                                        className="w-9 h-9 rounded-lg bg-neutral-800 flex items-center justify-center hover:bg-brand-500 transition-colors">
                                        <svg className="w-4 h-4"
                                            fill={isStroke ? 'none' : 'currentColor'}
                                            stroke={isStroke ? 'currentColor' : undefined}
                                            strokeWidth={isStroke ? 2 : undefined}
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            {icon}
                                        </svg>
                                    </a>
                                );
                            })}
                        </div>
                    </div>

                    {/* Nav columns */}
                    {navGroups.map((group, i) => (
                        <div key={i}>
                            <h3 className="text-sm font-semibold text-white mb-4">
                                {group[`title_${locale}`] ?? group.title_en ?? ''}
                            </h3>
                            <ul className="space-y-2.5 text-sm">
                                {(group.links || []).map((l, j) => (
                                    <li key={j}>
                                        <a href={l.href || '#'} className="hover:text-brand-400 transition-colors">
                                            {l[`label_${locale}`] ?? l.label_en ?? ''}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="border-t border-neutral-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-neutral-500">
                    <p>&copy; {new Date().getFullYear()} {brandName}. {copyright}</p>
                    <p>{builtWith}</p>
                </div>
            </div>
        </footer>
    );
}
