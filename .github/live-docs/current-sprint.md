# Current Sprint

**Sprint Name:** Domain Management — Nameservery & DNS w Portalu Klienta
**Date:** 2026-06-01 → bieżący
**Goal:** Umożliwienie klientowi zarządzania domeną z portalu — edycja nameserverów i pełny CRUD rekordów DNS przez OpenProvider API

## Backlog

### To Do
- (brak)

### In Progress
- (brak)

### Done
- [x] DOM-1 — Naprawa sandbox URL OpenProvider (`api.cte` → `api.sandbox.openprovider.nl:8480`) — 2026-06-01
- [x] DOM-2 — Izolacja credentiali sandbox (`op_sandbox_username` / `op_sandbox_password`) — 2026-06-01
- [x] DOM-3 — Fix formatu telefonu OP (`country_code` bez `+`, `area_code` = `'0'`) — 2026-06-01
- [x] DOM-4 — Fix pola adresu (`zip_code` → `zipcode`) — 2026-06-01
- [x] DOM-5 — Obsługa `code 10` (Registry not reachable — kolejkowanie) — 2026-06-01
- [x] DOM-6 — `DnsRecord` DTO + 4 metody DNS w `DomainRegistrarInterface` — 2026-06-01
- [x] DOM-7 — Implementacja DNS w `OpenProviderRegistrarService` (GET/POST/PUT/DELETE `/dns/zones/`) — 2026-06-01
- [x] DOM-8 — `FetchDnsRecordsAction`, `SaveDnsRecordAction`, `DeleteDnsRecordAction` — 2026-06-01
- [x] DOM-9 — `DomainDnsController` (index, store, update, destroy) — 2026-06-01
- [x] DOM-10 — Route `updateNameservers` + 4 trasy DNS w `routes/web.php` — 2026-06-01
- [x] DOM-11 — `DomainController::updateNameservers()` — 2026-06-01
- [x] DOM-12 — `Show.jsx` — modal edycji nameserverów + link "Manage DNS →" — 2026-06-01
- [x] DOM-13 — `Dns.jsx` — nowa strona: tabela DNS + modal add/edit + inline delete — 2026-06-01
- [x] DOM-14 — Weryfikacja: 321/321 testów ✅, build OK, route:list OK — 2026-06-01

## Status
**Translation Status:** ✅ Inline w JSX (EN/PL/PT)
**Test Coverage:** 321/321 ✅
**Multi-Tenancy Compliance:** 100% ✅
**Build:** ✅ brak błędów TS/JS

---

# Poprzedni Sprint — Google Calendar Hardening (2026-05-18)

- [x] GC-1 — Smart type detection (holiday/birthday → `reminder`)
- [x] GC-2 — Konfigurowalny zakres dat importu (modal z DatePicker)
- [x] GC-3 — Paginacja listy kalendarzy (`nextPageToken` loop)
- [x] GC-4 — Detekcja braku `refresh_token` + persistent warning z reconnect
- [x] GC-5 — Surface błędów kalendarzy do UI (warning notification)
- [x] GC-0 — Activity History, Sync All, Import multi-calendar, wire:ignore fix
- [x] T1–T5, multi-tenancy, ClientPortalAccess, ToggleColumn — 2026-05-17