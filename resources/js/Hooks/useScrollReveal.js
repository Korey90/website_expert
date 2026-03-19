import { useEffect } from 'react';

export default function useScrollReveal(selector = '.reveal') {
    useEffect(() => {
        const els = document.querySelectorAll(selector);
        if (!els.length) return;

        const observer = new IntersectionObserver(
            (entries) => entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    observer.unobserve(e.target);
                }
            }),
            { threshold: 0.15 }
        );

        els.forEach(el => observer.observe(el));
        return () => observer.disconnect();
    }, [selector]);
}
