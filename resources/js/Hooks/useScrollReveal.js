import { useEffect } from 'react';

export default function useScrollReveal(selector = '.reveal') {
    useEffect(() => {
        const io = new IntersectionObserver(
            (entries) => entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    io.unobserve(e.target);
                }
            }),
            { threshold: 0.15 }
        );

        const observe = (root) => {
            root.querySelectorAll(selector).forEach(el => {
                if (!el.classList.contains('visible')) io.observe(el);
            });
        };

        // Obserwuj elementy już w DOM
        observe(document);

        // Obserwuj nowe elementy dodawane przez React.lazy
        const mo = new MutationObserver(mutations => {
            mutations.forEach(m => m.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                if (node.matches?.(selector)) {
                    if (!node.classList.contains('visible')) io.observe(node);
                }
                observe(node);
            }));
        });

        mo.observe(document.body, { childList: true, subtree: true });

        return () => { io.disconnect(); mo.disconnect(); };
    }, [selector]);
}
