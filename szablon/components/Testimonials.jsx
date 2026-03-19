// Testimonials.jsx – WebsiteExpert (CDN React + Babel, prototyp)
// Mount: <div id="testimonials-root"></div>

const { useState, useEffect, useRef, useCallback } = React;

// ── Data (placeholder) ────────────────────────────────────────────────────────
const TESTIMONIALS = [
  {
    id: 1,
    name: 'Anna Kowalska',
    role: 'CEO, ModaBoutique',
    avatar: 'AK',
    avatarColor: 'bg-brand-500',
    company: 'Klient A',
    rating: 5,
    quote: 'Sklep wdrożony w 4 tygodnie. Konwersja wzrosła o 34% w pierwszym kwartale. Polecam bez wahania.',
  },
  {
    id: 2,
    name: 'Marek Błaszczyk',
    role: 'Właściciel, Kancelaria MB',
    avatar: 'MB',
    avatarColor: 'bg-neutral-600',
    company: 'Klient B',
    rating: 5,
    quote: 'Profesjonalizm na każdym etapie. Strona robi świetne pierwsze wrażenie na moich klientach.',
  },
  {
    id: 3,
    name: 'Piotr Wróbel',
    role: 'CTO, FleetTrack',
    avatar: 'PW',
    avatarColor: 'bg-brand-700',
    company: 'Klient C',
    rating: 5,
    quote: 'Aplikacja webowa z mapami i raportowaniem – dostarczona zgodnie z harmonogramem. Jakość kodu na wysokim poziomie.',
  },
  {
    id: 4,
    name: 'Katarzyna Nowak',
    role: 'Marketing Manager, FitLife',
    avatar: 'KN',
    avatarColor: 'bg-neutral-700',
    company: 'Klient D',
    rating: 5,
    quote: 'Kampania Google Ads przy współpracy z WebsiteExpert dała nam 3× więcej leadów niż poprzednia agencja.',
  },
  {
    id: 5,
    name: 'Tomasz Lewicki',
    role: 'Founder, EduStart',
    avatar: 'TL',
    avatarColor: 'bg-brand-600',
    company: 'Klient E',
    rating: 5,
    quote: 'SEO zaczęło działać po 2 miesiącach – jesteśmy w top 5 Google na kluczowe frazy. Rewelacyjna robota.',
  },
  {
    id: 6,
    name: 'Marta Zielińska',
    role: 'Dyrektor, KlinikaDent',
    avatar: 'MZ',
    avatarColor: 'bg-neutral-500',
    company: 'Klient F',
    rating: 5,
    quote: 'Responsywna wizytówka z systemem rezerwacji online. Pacjenci chwalą wygodę, a my mamy mniej telefonów.',
  },
];

// ── Stars component ───────────────────────────────────────────────────────────
function Stars({ count }) {
  return React.createElement('div', { className: 'flex gap-0.5', 'aria-label': `${count} gwiazdki` },
    Array.from({ length: 5 }, (_, i) =>
      React.createElement('svg', {
        key: i,
        className: `w-4 h-4 ${i < count ? 'text-yellow-400' : 'text-neutral-200 dark:text-neutral-700'}`,
        fill: 'currentColor', viewBox: '0 0 20 20', 'aria-hidden': 'true'
      },
        React.createElement('path', { d: 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z' })
      )
    )
  );
}

// ── Single card ───────────────────────────────────────────────────────────────
function TestimonialCard({ item }) {
  return React.createElement('article', {
    className: 'testimonial-card flex-shrink-0 w-full sm:w-80 mx-2',
    'aria-label': `Opinia od ${item.name}`
  },
    React.createElement('div', { className: 'mb-3' },
      React.createElement(Stars, { count: item.rating })
    ),
    React.createElement('blockquote', { className: 'text-sm text-neutral-700 dark:text-neutral-300 leading-relaxed mb-4' },
      React.createElement('p', null, `"${item.quote}"`)
    ),
    React.createElement('div', { className: 'flex items-center gap-3' },
      React.createElement('div', {
        className: `w-10 h-10 rounded-full ${item.avatarColor} flex items-center justify-center text-white text-sm font-bold shrink-0`,
        'aria-hidden': 'true'
      }, item.avatar),
      React.createElement('div', null,
        React.createElement('p', { className: 'text-sm font-semibold text-neutral-900 dark:text-white' }, item.name),
        React.createElement('p', { className: 'text-xs text-neutral-500 dark:text-neutral-400' }, item.role)
      )
    )
  );
}

// ── Carousel ──────────────────────────────────────────────────────────────────
function Testimonials() {
  const [current, setCurrent] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const trackRef = useRef(null);
  const total = TESTIMONIALS.length;

  const goTo = useCallback((index) => {
    setCurrent((index + total) % total);
  }, [total]);

  // Auto-advance every 5s
  useEffect(() => {
    if (isPaused) return;
    const id = setInterval(() => goTo(current + 1), 5000);
    return () => clearInterval(id);
  }, [current, isPaused, goTo]);

  // Keyboard navigation
  const handleKeyDown = (e) => {
    if (e.key === 'ArrowLeft') goTo(current - 1);
    if (e.key === 'ArrowRight') goTo(current + 1);
  };

  return React.createElement('div', {
    className: 'relative',
    onMouseEnter: () => setIsPaused(true),
    onMouseLeave: () => setIsPaused(false),
    onKeyDown: handleKeyDown,
    tabIndex: 0,
    'aria-label': 'Karuzela opinii klientów',
    role: 'region',
  },
    // Track
    React.createElement('div', { className: 'overflow-hidden', 'aria-live': 'polite' },
      React.createElement('div', {
        ref: trackRef,
        className: 'flex transition-transform duration-500 ease-in-out',
        style: { transform: `translateX(calc(-${current * 100}% / 1))` }
      },
        // Show all cards, CSS handles visibility via transform
        TESTIMONIALS.map((item, idx) =>
          React.createElement('div', {
            key: item.id,
            className: 'w-full flex-shrink-0 px-2',
            'aria-hidden': idx !== current ? 'true' : undefined,
          },
            React.createElement('div', { className: 'max-w-2xl mx-auto' },
              React.createElement(TestimonialCard, { item })
            )
          )
        )
      )
    ),

    // Navigation dots + arrows
    React.createElement('div', { className: 'flex items-center justify-center gap-4 mt-6' },
      // Prev
      React.createElement('button', {
        type: 'button',
        onClick: () => goTo(current - 1),
        'aria-label': 'Poprzednia opinia',
        className: 'w-9 h-9 rounded-full border border-neutral-200 dark:border-neutral-700 flex items-center justify-center text-neutral-500 dark:text-neutral-400 hover:border-brand-500 hover:text-brand-500 transition-colors'
      },
        React.createElement('svg', { className: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', strokeWidth: 2, 'aria-hidden': 'true' },
          React.createElement('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M15 19l-7-7 7-7' })
        )
      ),

      // Dots
      React.createElement('div', { className: 'flex gap-1.5', role: 'tablist', 'aria-label': 'Wybierz opinię' },
        TESTIMONIALS.map((_, idx) =>
          React.createElement('button', {
            key: idx,
            type: 'button',
            role: 'tab',
            'aria-selected': idx === current,
            'aria-label': `Opinia ${idx + 1}`,
            onClick: () => goTo(idx),
            className: `h-2 rounded-full transition-all duration-300 ${
              idx === current
                ? 'w-6 bg-brand-500'
                : 'w-2 bg-neutral-300 dark:bg-neutral-600 hover:bg-brand-300'
            }`
          })
        )
      ),

      // Next
      React.createElement('button', {
        type: 'button',
        onClick: () => goTo(current + 1),
        'aria-label': 'Następna opinia',
        className: 'w-9 h-9 rounded-full border border-neutral-200 dark:border-neutral-700 flex items-center justify-center text-neutral-500 dark:text-neutral-400 hover:border-brand-500 hover:text-brand-500 transition-colors'
      },
        React.createElement('svg', { className: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', strokeWidth: 2, 'aria-hidden': 'true' },
          React.createElement('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M9 5l7 7-7 7' })
        )
      )
    )
  );
}

// ── Mount ─────────────────────────────────────────────────────────────────────
const testimonialsRoot = document.getElementById('testimonials-root');
if (testimonialsRoot) {
  ReactDOM.createRoot(testimonialsRoot).render(React.createElement(Testimonials));
}
