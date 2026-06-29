import { createHeadManager } from '@inertiajs/core';
import { describe, expect, it } from 'vitest';

const descriptions = {
    pl: 'Profesjonalne projektowanie stron internetowych, e-commerce i usługi SEO w Belfaście oraz w całej Irlandii Północnej. Stała cena, realizacja w 2–6 tygodni. Bezpłatna wycena w ciągu 24 godzin — website-expert.uk',
    en: 'Professional web design, e-commerce and SEO services in Belfast and across Northern Ireland. Fixed price, delivered in 2–6 weeks. Free quote in 24 hours — website-expert.uk',
    pt: 'Serviços profissionais de web design, comércio eletrónico e SEO em Belfast e em toda a Irlanda do Norte. Preço fixo, entrega em 2–6 semanas. Orçamento gratuito em 24 horas — website-expert.uk',
};

describe.each(Object.entries(descriptions))('homepage SEO for %s', (locale, description) => {
    it('keeps exactly one localized description after Inertia updates the head', async () => {
        document.head.innerHTML = `<meta inertia="description" name="description" content="${description}">`;

        const manager = createHeadManager(false, (title) => title, () => {});
        const provider = manager.createProvider();

        provider.update([
            `<meta inertia="description" name="description" content="${description}">`,
        ]);

        await new Promise((resolve) => setTimeout(resolve, 10));

        const tags = document.head.querySelectorAll('meta[name="description"]');

        expect(tags).toHaveLength(1);
        expect(tags[0]).toHaveAttribute('content', description);

        provider.disconnect();
    });
});
