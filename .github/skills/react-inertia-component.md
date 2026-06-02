# Skill: React + Inertia Component

**Opis:** Tworzenie lub modyfikacja komponentów React z Inertia.js + TypeScript.

**Kiedy używać:** Każdy nowy UI component, Inertia page, lub custom hook.

---

## Wzorzec Inertia Page (kompletny)

```tsx
// resources/js/Pages/{Domain}/Index.tsx
import { Head, Link, router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface {Model} {
  id: number;
  name: string;
  status: 'active' | 'inactive';
  created_at: string;
}

interface Props {
  items: {Model}[];
  filters: {
    search?: string;
    status?: string;
  };
}

export default function Index({ items, filters }: Props) {
  const { data, setData, get } = useForm({
    search: filters.search ?? '',
    status: filters.status ?? '',
  });

  const search = () => get(route('{domain}.index'));

  return (
    <AuthenticatedLayout>
      <Head title={trans('{domain}.index_title')} />
      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          {/* Filters */}
          <input
            type="text"
            value={data.search}
            onChange={(e) => setData('search', e.target.value)}
            placeholder={trans('{domain}.search_placeholder')}
            className="rounded-md border px-3 py-2"
          />
          {/* Table */}
          <table className="mt-4 w-full">
            {items.map((item) => (
              <tr key={item.id}>
                <td>{item.name}</td>
              </tr>
            ))}
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
```

---

## Wzorzec komponentu z mutacją

```tsx
// resources/js/Components/{Domain}/{Model}Form.tsx
import { useForm } from '@inertiajs/react';

interface {Model}FormProps {
  initialData?: {
    name: string;
    status: 'active' | 'inactive';
  };
  onSuccess?: () => void;
}

export function {Model}Form({ initialData, onSuccess }: {Model}FormProps) {
  const { data, setData, post, put, processing, errors } = useForm({
    name: initialData?.name ?? '',
    status: initialData?.status ?? 'active',
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('{domain}.store'), { onSuccess });
  };

  return (
    <form onSubmit={submit}>
      <div>
        <label htmlFor="name">{trans('{domain}.name')}</label>
        <input
          id="name"
          value={data.name}
          onChange={(e) => setData('name', e.target.value)}
          className="mt-1 block w-full rounded-md border"
        />
        {errors.name && <p className="text-red-500 text-sm">{errors.name}</p>}
      </div>
      <button type="submit" disabled={processing}>
        {trans('common.save')}
      </button>
    </form>
  );
}
```

---

## Wzorzec custom hook

```tsx
// resources/js/Hooks/use{Model}.ts
import { useForm } from '@inertiajs/react';

interface Use{Model}Options {
  onSuccess?: () => void;
}

export function use{Model}({ onSuccess }: Use{Model}Options = {}) {
  const { post, delete: destroy, processing } = useForm({});

  const activate = (id: number) =>
    post(route('{domain}.activate', id), { onSuccess });

  const deactivate = (id: number) =>
    post(route('{domain}.deactivate', id), { onSuccess });

  const remove = (id: number) =>
    destroy(route('{domain}.destroy', id), { onSuccess });

  return { activate, deactivate, remove, processing };
}
```

---

## Checklist po stworzeniu komponentu

- [ ] Wszystkie props typowane (interface, nie inline)
- [ ] Zero `any`
- [ ] Teksty UI przez `trans()` z kluczem tłumaczenia
- [ ] Obsługa `errors` przy formularzach
- [ ] Stan `processing` (disabled podczas submit)
- [ ] Tłumaczenia → @DocumentationEngineer
- [ ] Test → skill: test-generation (Vitest)
