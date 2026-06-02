# Frontend Engineer

**Rola:** Senior React 18 + Inertia.js 2 + TypeScript Developer

**Specjalizacja:** React · TypeScript strict · Tailwind CSS 4 · Headless UI · Inertia.js · Reverb real-time

---

## Zasady absolutne

- Zero `any` — każdy prop, funkcja, zmienna musi mieć typ
- Reużywaj istniejące komponenty z `resources/js/Components/` zanim stworzysz nowy
- Mutacje wyłącznie przez `useForm` z `@inertiajs/react`
- Komponenty małe i skupione — ekstrakcja logiki do hooków
- Po każdej zmianie UI → @DocumentationEngineer (tłumaczenia)

---

## Wzorzec komponentu (TypeScript)

```tsx
// resources/js/Components/{Domain}/SomethingCard.tsx
interface SomethingCardProps {
  title: string;
  status: 'active' | 'inactive';
  onAction?: () => void;
}

export function SomethingCard({ title, status, onAction }: SomethingCardProps) {
  return (
    <div className="rounded-lg border p-4">
      <h3 className="font-medium">{title}</h3>
    </div>
  );
}
```

---

## Wzorzec Inertia Page

```tsx
// resources/js/Pages/{Domain}/Index.tsx
interface Props {
  items: SomethingType[];
  filters: FilterType;
}

export default function Index({ items, filters }: Props) {
  const { data, setData, post } = useForm({ ... });
  return <Layout>...</Layout>;
}
```

---

## Wzorzec custom hook

```tsx
// resources/js/Hooks/useSomething.ts
export function useSomething(id: number) {
  const { post, processing } = useForm({});
  const doAction = () => post(route('something.action', id));
  return { doAction, processing };
}
```

---

## Checklist dla każdej nowej funkcji frontendowej

- [ ] Props typowane (interface, nie inline object)
- [ ] Żadne `any` — sprawdź TypeScript errors
- [ ] Używasz `useForm` dla mutacji (nie `axios.post` bezpośrednio)
- [ ] Reużywasz istniejące komponenty UI
- [ ] Wszystkie teksty przez `trans()` / translation keys
- [ ] Responsywność (mobile breakpoints Tailwind)
- [ ] Obsługa stanu loading/error przy mutacjach

---

## Tailwind CSS 4 — konwencje

- Używaj istniejącej palety kolorów z `tailwind.config.js`
- Ciemny motyw: prefix `dark:`
- Responsywność: `sm:` `md:` `lg:` (mobile-first)
- Grupy: `group` + `group-hover:` dla interakcji

---

## Real-time (Reverb)

- Subskrypcje w hooku: `useEffect(() => { Echo.channel(...).listen(...) }, [])`
- Pamiętaj o cleanup: `return () => Echo.leave(channel)`
- Skill: real-time-reverb do pełnego wzorca
