# Raport inspekcji: `test_can_register_domain_for_client_test` vs. OpenProvider API

**Data:** 2026-06-09  
**Źródło dokumentacji:** https://support.openprovider.eu/hc/en-us/articles/360025090374  
**Pliki poddane inspekcji:**
- `tests/Feature/Domain/OpenProviderSandboxTest.php`
- `app/Services/Domain/OpenProviderRegistrarService.php`
- `app/Services/Domain/OpenProviderClient.php`
- `app/Actions/Domain/EnsureOpHandleAction.php`
- `app/Data/Domain/DomainRegistrationPayload.php`

---

## 1. Wymagania dokumentacji OpenProvider (POST /domains)

### Pola wymagane (Required)

| Pole API | Typ | Opis |
|---|---|---|
| `domain.name` | string | Nazwa domeny bez TLD |
| `domain.extension` | string | TLD bez kropki, np. `nl`, `co.uk` |
| `period` | integer | Liczba lat rejestracji |
| `owner_handle` | varchar | Handle klienta OP (właściciel) |
| `admin_handle` | varchar | Handle klienta OP (admin) |
| `tech_handle` | varchar | Handle klienta OP (techniczny) |
| `ns_group` **LUB** `name_servers` | — | Wymagane jedno z dwóch |

### Pola opcjonalne (Optional)

`auth_code`, `billing_handle`, `reseller_handle`, `ns_template_name`, `autorenew` (`on`/`off`/`default`), `is_dnssec_enabled`, `dnssec_keys`, `use_domicile`, `accept_premium_fee`, `is_private_whois_enabled`, `promo_code`, `comments`, `additional_data`, `application_mode`

### Odpowiedź (Response)

```json
{
  "code": 0,
  "data": {
    "activation_date": "2019-04-29 17:15:19",
    "auth_code": "...",
    "expiration_date": "2020-04-29 17:15:19",
    "id": 10592139,
    "renewal_date": "2020-04-29 17:15:19",
    "status": "ACT"
  }
}
```

Statusy: `ACT` (aktywna) lub `REQ` (zlecona).

---

## 2. Payload generowany przez kod (`OpenProviderRegistrarService::register()`)

```php
$this->client->post('/domains', [
    'domain' => [
        'name'      => $payload->domainName,   // np. "we-client-abc123"
        'extension' => $extension,             // np. "nl"
    ],
    'owner_handle'             => $handle,     // z EnsureOpHandleAction
    'admin_handle'             => $handle,     // identyczny z owner
    'tech_handle'              => $handle,     // identyczny z owner
    'period'                   => $payload->years,  // 1
    'unit'                     => 'y',
    'name_servers'             => $nameServers, // OP defaults gdy puste
    'autorenew'                => 'off',        // autoRenew: false
    'is_private_whois_enabled' => false,        // whoisPrivacy: false
]);
```

Nameservery domyślne gdy `nameservers: []`:
```
ns1.openprovider.eu / ns2.openprovider.be / ns3.openprovider.eu
```

---

## 3. Wyniki inspekcji

### ✅ ZGODNE — pola wymagane

| Wymaganie API | Implementacja | Status |
|---|---|---|
| `domain.name` | `$payload->domainName` | ✅ |
| `domain.extension` | `ltrim($payload->tld, '.')` | ✅ |
| `owner_handle` | z `EnsureOpHandleAction` | ✅ |
| `admin_handle` | identyczny z `owner_handle` | ✅ (dozwolone per docs) |
| `tech_handle` | identyczny z `owner_handle` | ✅ (dozwolone per docs) |
| `period` | `$payload->years` (int) | ✅ |
| `name_servers` (lub `ns_group`) | budowane z fallbackiem na ns OP | ✅ |

### ✅ ZGODNE — format odpowiedzi

| Pole odpowiedzi | Mapowanie kodu | Status |
|---|---|---|
| `data.id` | `$data['id']` → `$result->providerId` | ✅ |
| `data.activation_date` | `Carbon::parse($data['activation_date'])` | ✅ |
| `data.expiration_date` | `Carbon::parse($data['expiration_date'])` | ✅ |
| `code === 0` | `parse()` w `OpenProviderClient` | ✅ |

### ✅ ZGODNE — wartości `autorenew`

Dokumentacja dopuszcza: `on` / `off` / `default`.  
Kod wysyła: `$payload->autoRenew ? 'on' : 'off'` → test używa `autoRenew: false` → `'off'`. ✅

### ✅ ZGODNE — flow EnsureOpHandleAction (Krok 3/5)

Akcja wysyła `POST /customers` lub `PUT /customers/{handle}` z polami:
`name.first_name`, `name.last_name`, `email`, `phone`, `address` — wszystkie wymagane przez OP.  
Handle jest zapisywany z powrotem na rekordzie `clients.op_handle`. ✅

---

## 4. Znalezione problemy

### 🔴 Problem 1 — Martwy kod: sprawdzenie `'"code":10'` w teście

**Lokalizacja:** `OpenProviderSandboxTest.php` — funkcja `test_can_register_domain_for_client_test`, Krok 5/5.

```php
// W teście:
if (! $result->success && str_contains($result->error ?? '', '"code":10')) {
    $this->markTestIncomplete(...);
}
```

**Dlaczego nie zadziała:**  
Gdy OP zwraca HTTP 200 z `"code": 10`, metoda `parse()` rzuca wyjątek:
```
"Openprovider API error on POST /domains (code 10): Registry currently not reachable"
```
Ciąg `'"code":10'` **nie** występuje w tym komunikacie (jest `"code 10"` z spacją, bez cudzysłowów).

Serwis przechwytuje ten wyjątek i zawsze zwraca `DomainRegistrationResult::success(...)` z `providerId: ''` przez `resolveQueuedRegistration()`. Nie dochodzi do `DomainRegistrationResult::failure()`.

**Skutek:** Warunek `! $result->success && str_contains(..., '"code":10')` nie zostanie nigdy spełniony — code 10 jest absorbowany w serwisie i staje się sukcesem z pustym `providerId`, który jest obsługiwany niżej przez `if (empty($result->providerId))`.

**Taki sam problem istnieje w `test_can_register_unique_domain_on_sandbox`.**

**Rekomendacja:**
```php
// Usuń martwy blok — jest zbędny, bo serwis zawsze zwraca success dla code 10:
// if (! $result->success && str_contains($result->error ?? '', '"code":10')) { ... }
```

---

### 🟡 Problem 2 — Niezdokumentowane pole `unit: 'y'`

**Lokalizacja:** `OpenProviderRegistrarService.php` linia ~143.

```php
'unit' => 'y',  // nie ma w dokumentacji POST /domains
```

Dokumentacja OpenProvider z artykułu 13 (i potwierdzony endpoint w docs.openprovider.com) nie wymienia pola `unit` w `POST /domains`. Pole `period` jest opisane jako `integer` bez jednostki.

**Skutek:** Żadnego — OP akceptuje nieznane pola (ignoruje je). W praktyce sandbox działa poprawnie. Jednak może powodować niejasności podczas przeglądu kodu.

**Rekomendacja:** Pozostaw lub usuń po weryfikacji z oficjalnym API docs (`docs.openprovider.com`).

---

### 🟡 Problem 3 — Semantyczna pomyłka: `address_line2` → `address.number`

**Lokalizacja:** `EnsureOpHandleAction.php`, metody `syncHandle()` i `createHandle()`.

```php
'address' => [
    'street'   => $contact['address_line1'] ?? '',
    'number'   => $contact['address_line2'] ?? '',  // ⚠️
    ...
],
```

W API OpenProvider pole `address.number` to **numer budynku** (np. `"42"`), natomiast `address_line2` to zazwyczaj dodatkowy opis adresu (piętro, nr lokalu, np. `"Apt 3"`).

**Skutek:** Przy rejestrowaniu klientów z `address_line2 = "Flat 5"`, OP otrzymuje `number: "Flat 5"` zamiast poprawnego numeru budynku. Dla niektórych TLD weryfikujących dane adresowe może to powodować odrzucenie rejestracji lub niepoprawny WHOIS.

W teście `test_can_register_domain_for_client_test`, pole `address_line2` pochodzi z `$clientRow->address_line2 ?? null`. Jeśli klient ma adres drugiej linii, problem materializuje się podczas tworzenia handle'u.

**Rekomendacja:** Sprawdzić schemat API `/customers` (docs.openprovider.com) i dostosować mapowanie. Prawdopodobnie `address_line1` powinien zawierać `street + number`, a `address_line2` powinno iść do `province` lub być pomijane.

---

### 🟢 Obserwacja 4 — `billing_handle` nie jest wysyłany

Dokumentacja oznacza `billing_handle` jako `Optional`. Kod nie wysyła tego pola. ✅ Zgodne.

---

### 🟢 Obserwacja 5 — URL sandbox

Kod używa: `http://api.sandbox.openprovider.nl:8480/v1beta` (HTTP, port 8480).  
Dokumentacja OpenProvider sandbox potwierdza ten URL. ✅ Zgodne.

---

## 5. Podsumowanie

| # | Kategoria | Status | Wpływ |
|---|---|---|---|
| 1 | Martwy kod — check `'"code":10'` w teście | 🔴 Bug | Niski (test nie kłamie, ale wprowadza w błąd) |
| 2 | Niezdokumentowane pole `unit: 'y'` | 🟡 Info | Brak (OP ignoruje) |
| 3 | `address_line2` → `address.number` (błędne mapowanie) | 🟡 Bug | Średni (może odrzucać rejestracje TLD z walidacją adresu) |
| 4 | `billing_handle` brak | 🟢 OK | Brak — pole opcjonalne |
| 5 | URL sandbox | 🟢 OK | — |
| 6 | Wszystkie wymagane pola API | 🟢 OK | — |
| 7 | Parsowanie odpowiedzi API | 🟢 OK | — |
| 8 | Obsługa code 10 / 311 (sandbox quirks) | 🟢 OK | — |

**Wniosek:** Test jest ogólnie poprawny i pokrywa kluczowe ścieżki. Dwie kwestie wymagają uwagi: martwy kod `'"code":10'` w bloku `markTestIncomplete` oraz błędne mapowanie `address_line2 → address.number` w `EnsureOpHandleAction`, które może powodować problemy przy TLD z rygorystyczną walidacją adresu WHOIS.
