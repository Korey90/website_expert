# Raport panelu administratora

Data analizy: 2026-07-18

## Zakres i zrodla

Raport opisuje faktyczny panel administratora obecny w kodzie projektu. Nie korzystano z plikow w `docs/` jako zrodla prawdy, poniewaz moga byc nieaktualne. Analiza opiera sie na:

- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Resources/*Resource.php`
- `app/Filament/Resources/**/Pages/*.php`
- `app/Filament/Resources/**/RelationManagers/*.php`
- `app/Filament/Pages/*.php`
- `app/Filament/Widgets/*.php`
- `routes/web/admin.php` i `routes/web/calendar.php` dla tras pomocniczych panelu
- `app/Models/User.php` oraz `app/Filament/Support/FilamentPermissionRegistry.php` dla dostepu i uprawnien

Panel administratora jest panelem Filament o identyfikatorze `admin`, sciezce `/admin`, brandzie `WebsiteExpert`, wlasnym theme `resources/css/filament/admin/theme.css`, logowaniem Filament, powiadomieniami bazodanowymi i automatycznym odkrywaniem zasobow, stron oraz widgetow z `app/Filament`.

## Dostep, uprawnienia i tenanty

Dostep do panelu jest kontrolowany przez `User::canAccessPanel()`. Uzytkownik musi byc aktywny (`is_active`) i miec uprawnienie `access_admin_panel` sprawdzane przez `PermissionHelper` i `FilamentPermissionRegistry`.

Wiekszosc zasobow dziedziczy po `BaseResource`, ktory mapuje akcje Filament (`view`, `create`, `update`, `delete`, restore, force delete, reorder, replicate) na konkretne permissions. Strony dziedziczace po `BasePage` rowniez sprawdzaja permissions z `FilamentPermissionRegistry::pagePermission()`.

Panel jest multi-tenantowy, ale zasoby administracyjne dla uzytkownikow z uprawnieniem `access_admin_panel` czesto zdejmują `BusinessScope`, dzieki czemu staff widzi dane ze wszystkich tenantow. Tak dzialaja m.in. `LeadResource`, `ClientResource`, `LandingPageResource`, `DomainResource`, `DomainOrderResource` i `SalesOfferResource`. W tabelach czesto pojawia sie kolumna `business` lub filtr `business_id`, jesli staff ma pelny dostep.

## Uklad panelu

Provider definiuje glowne grupy nawigacji: CRM, Sales, Projects, Finance, Marketing, Automation, Reports, SaaS Billing i Settings. W samych zasobach istnieja dodatkowe grupy: Domains, Productivity i System.

Globalne elementy UI:

- topbar renderuje przypiete notatki leadow (`LeadNote::where('is_pinned', true)`) w komponencie `filament.components.pinned-notes`;
- panel ma dzwonek powiadomien Filament (`databaseNotifications()`);
- na koncu body ladowany jest skrypt `resources/js/admin/notifications.js` z `window.AdminPanelConfig.userId`;
- sidebar jest zwijany na desktopie;
- panel ma kolory: primary `#ff2b17`, gray Zinc.

## Dashboard

Glowny dashboard (`AdminDashboard`) ma responsywny uklad 2 kolumny od `md` i 3 kolumny od `xl`. Widgety:

- `QuickActionsWidget` - szybkie akcje panelu;
- `StatsOverviewWidget` - aktywne projekty, nowe leady w miesiacu, przeterminowane faktury, przychod miesiaca grupowany per waluta;
- `SaasMetricsWidget` - MRR, ARR, aktywni subskrybenci, trialing, churn 30d, past due;
- `RevenueChartWidget` - wykres przychodow;
- `LeadsBySourceWidget` - wykres leadow po zrodle;
- `ProjectStatusWidget` - wykres statusow projektow;
- `OverdueInvoicesWidget` - tabela przeterminowanych faktur z linkiem do edycji;
- `RecentLeadsWidget` - ostatnie leady z linkiem do edycji;
- `ProjectDeadlinesWidget` - projekty z terminami i przypisanym uzytkownikiem;
- `StaleLeadsWidget` - leady wymagajace follow-up.

Dodatkowo istnieja widgety domenowe i kalkulatora (`DomainExpiryWidget`, `DomainOrderStatsWidget`, `CalculatorPricingTableWidget`, `CalculatorStepsTableWidget`, `CalculatorStringsTableWidget`) oraz `SecurityStatsWidget`, ale nie sa wpisane do glownego `AdminDashboard::getWidgets()`.

## CRM

### Klienci

`ClientResource` oferuje CRUD klientow z widokiem szczegolowym. Przechowuje dane firmy, status (`prospect`, `active`, `inactive`, `archived`), branze, adres, strone WWW, Companies House, VAT, zrodlo, glowne dane kontaktowe, walute, LTV, opiekuna i notatki. Pokazuje status dostepu do portalu klienta, login portalu i ostatnie logowanie. Ma preferencje komunikacji: e-maile transakcyjne, aktualizacje projektowe, marketing/automatyzacje i SMS.

Tabela klientow pozwala wyszukiwac po firmie i e-mailu, filtrowac po statusie oraz obslugiwac soft deletes przez `TrashedFilter`, restore i force delete.

### Leady

`LeadResource` oferuje CRUD leadow. Formularz zawiera tytul, klienta z mozliwoscia utworzenia nowego klienta, etap pipeline, przypisanego uzytkownika, zrodlo, wartosc, walute, przewidywana date zamkniecia, dane kalkulatora i notatki.

Tabela pokazuje tytul, tenant, klienta, etap, wartosc, zrodlo, typ zrodla (`landing_page`, `contact_form`, `calculator`, `api`), powiazana landing page, przypisanego uzytkownika i daty. Filtry obejmuja etap, zrodlo, typ zrodla i rekordy usuniete.

Akcje specjalne leadow:

- `Conduct Briefing` - tworzy briefing z aktywnego szablonu przez `BriefingService` i przenosi do widoku briefingu;
- `Wyslij oferte` - tworzy oferte sprzedazowa z szablonu przez `SalesOfferService`, probuje ja wyslac i przenosi do widoku oferty;
- `Zapisz oferte (draft)` - tworzy draft oferty sprzedazowej z szablonu;
- standardowe view, edit i delete.

### Sales Pipeline

`PipelinePage` to osobna strona kanban/lista pipeline. Pobiera etapy z `PipelineStage`, grupuje leady po etapach i liczy wartosci per waluta. Oferuje:

- przejscie do listy leadow i utworzenie nowego leada;
- przesuwanie leada miedzy etapami;
- oznaczenie jako won/lost na podstawie etapow `is_won` i `is_lost`;
- konwersje leada do projektu;
- przypisanie leada do siebie;
- soft-delete leada;
- modal wysylki e-maila do klienta z aktywnego `EmailTemplate` i podstawieniem zmiennych;
- modal notatek z dodawaniem, edycja, usuwaniem i przypinaniem notatek;
- modal historii aktywnosci z `LeadActivity`.

### Etapy pipeline, briefingi i oferty

`PipelineStageResource` zarzadza etapami pipeline. `BriefingResource` i `BriefingTemplateResource` zarzadzaja briefingami oraz szablonami briefingow. `SalesOfferResource` pokazuje oferty sprzedazowe, ich lead, autora, jezyk, status (`draft`, `sent`, `viewed`, `converted`), date wyslania i obejrzenia. `SalesOfferTemplateResource` zarzadza szablonami ofert.

## Sales

Grupa Sales zawiera `BriefingTemplateResource` i `SalesOfferTemplateResource`. Szablony sa uzywane przez akcje na leadach do tworzenia briefingow i ofert. Szablony ofert sa filtrowane wedlug aktywnosci, jezyka i opcjonalnego `service_slug`.

## Projekty

`ProjectResource` oferuje CRUD projektow, widok szczegolowy i dodatkowa strone `tasks` (`ManageProjectTasks`). Formularz obsluguje tytul, klienta, lead developera, status (`draft`, `active`, `on_hold`, `completed`, `cancelled`), typ uslugi, szablon projektu, powiazane zamowienie domeny, walute, budzet, date startu, deadline i opis.

Widok projektu zawiera:

- pasek statystyk: postep taskow, postep faz, aktywna faza, budzet/faktury, deadline;
- sekcje faz projektu;
- panel szczegolow: klient, lead developer, typ uslugi, daty, status, finanse;
- quick actions przez widok Blade;
- liste powiazanych faktur;
- opis projektu;
- relation manager `MessagesRelationManager` dla wiadomosci.

Tabela projektow pokazuje tytul, klienta, typ uslugi, status, budzet, deadline i developera. Filtry: status, typ uslugi, rekordy usuniete. `ProjectTemplateResource` zarzadza szablonami projektow.

## Finance

### Faktury

`InvoiceResource` oferuje CRUD faktur z numerem domyslnym `INV-{rok}-{numer}`, klientem, projektem, opcjonalnym zamowieniem domeny, statusem (`draft`, `sent`, `partially_paid`, `paid`, `overdue`, `cancelled`), waluta, VAT, data wystawienia i terminem platnosci. Faktury maja repeater pozycji (`description`, `quantity`, `unit_price`, `amount`), pola sum (`subtotal`, `discount_amount`, `vat_amount`, `total`), notatki i terms.

Tabela pokazuje numer, klienta, status, total, amount due, due date i czy wyslana. Akcja `PDF` otwiera trase `invoice.pdf` w nowej karcie.

### Wyceny, kontrakty i platnosci

`QuoteResource` zarzadza wycenami z numerem `QUOT-{rok}-{numer}`, klientem, opcjonalnym zamowieniem domeny, statusem (`draft`, `sent`, `accepted`, `rejected`, `expired`), waluta, VAT, waznoscia, pozycjami, notatkami i terms.

`ContractResource` oraz `ContractTemplateResource` zarzadzaja kontraktami i szablonami kontraktow. `PaymentResource` pokazuje platnosci. W panelu istnieja tez trasy raportow eksportu dla leadow, faktur i projektow w `routes/web/admin.php`.

## Domeny

Grupa Domains istnieje w zasobach, mimo ze nie jest jawnie wpisana w `AdminPanelProvider`.

`DomainPriceListResource` zarzadza cennikiem TLD per waluta. Formularz ma TLD, walute, marze, aktywnosc, ceny detaliczne rejestracji/odnowienia/transferu, ceny hurtowe Openprovider i notatki. Tabela pokazuje ceny detaliczne i hurtowe, marze, walute, aktywnosc i date aktualizacji. Bulk actions: activate, deactivate, delete. Dodatkowe trasy admina umozliwiaja eksport cennika i szablonu CSV.

`DomainOrderResource` pokazuje zamowienia domen: domena, status (`pending_payment`, `paid`, `registering`, `completed`, `failed`, `cancelled`), akcja (`register`, `renew`, `transfer`), lata, provider, retail/wholesale price, waluta, Stripe payment intent, klient, tenant, utworzyl, zarejestrowana domena, notatki, event log oraz powiazane faktury, wyceny i projekty. Zasob jest tylko index/view.

`DomainResource` pokazuje domeny: status (`active`, `pending`, `expired`, `transferred`, `cancelled`), provider, provider domain id, daty rejestracji i wygasniecia, auto-renew, WHOIS privacy, nameservery, klienta, zamowienie, tenant i event log. Zasob jest tylko index/view.

Dashboard domenowy istnieje w postaci widgetow: `DomainOrderStatsWidget` i `DomainExpiryWidget`.

## Marketing i CMS

`LandingPageResource` zarzadza landing pages: tytul, slug z prefiksem `lp/`, jezyk (`en`, `pl`, `pt`), status (`draft`, `published`, `archived`), template key, conversion goal, opis, SEO (`meta_title`, `meta_description`) i custom CSS. Tabela pokazuje tenant, tytul, slug, status, jezyk, views, leads, published date i created date; filtry obejmuja tenant, status i jezyk.

`NavbarSettingsPage` zarzadza menu publicznej strony: brand name, widocznosc language switchera, toggla dark/light, przycisku portalu klienta oraz CTA. CTA ma URL i teksty PL/EN/PT. Strona ma tez tabele `NavItem` z kolejnoscia, labelami tlumaczonymi per locale, URL, `section_key`, open in new tab i aktywnoscia. Elementy menu mozna dodawac, edytowac, usuwac i reorderowac.

Pozostale zasoby marketingowe:

- `EmailTemplateResource` - szablony e-maili;
- `SmsTemplateResource` - szablony SMS;
- `ServiceItemResource` - uslugi;
- `ServicePageResource` - strony uslug;
- `PortfolioProjectResource` - projekty portfolio;
- `SiteSectionResource` - sekcje frontendu;
- `PageResource` - CMS pages;
- `NavItemResource` - menu jako osobny zasob.

## Kalkulator

`CalculatorAdminPage` ma widok `filament.pages.calculator-admin` i tytul `Calculator Management`. Obok istnieja zasoby i widgety zarzadzajace kalkulatorem:

- `CalculatorStepsResource` / `CalculatorStepsTableWidget` - kroki kalkulatora, aktywnosc, kolejnosc, pytania i hinty EN/PL/PT;
- `CalculatorPricingResource` / `CalculatorPricingTableWidget` - opcje cenowe, ikony, etykiety/opisy EN/PL/PT, koszt bazowy, mnoznik, koszt miesieczny, dynamic formula, waluta, aktywnosc i kolejnosc;
- `CalculatorStringsResource` / `CalculatorStringsTableWidget` - stringi UI kalkulatora po grupach, z wartosciami EN/PL/PT, notatka admina i sort order.

## Automatyzacje

`AutomationRuleResource` pozwala definiowac reguly automatyzacji. Regula ma nazwe, aktywny trigger z `AutomationTrigger`, delay w minutach, aktywnosc, warunki i akcje.

Fallback triggerow obejmuje m.in. `lead.created`, `lead.stage_changed`, `project.created`, `project.status_changed`, `invoice.sent`, `invoice.overdue`, `invoice.paid`, `quote.sent`, `quote.accepted`, `contract.created`, `contract.sent`, `contract.signed`, `contract.expired`, `lead.service_cta`, `lead.contact_form`.

Warunki wspieraja pola takie jak `source`, `status`, `stage_id`, `assignee_id`, `lead_id`, `client_id`, `business_id`, UTM source/medium/campaign oraz operatory `=`, `!=`, `>`, `<`, `>=`, `<=`, `contains`.

Akcje automatyzacji:

- send email z aktywnego `EmailTemplate`;
- send SMS z aktywnego `SmsTemplate`;
- notify admin w dzwonku Filament z tytulem, body, URL, ikona, kolorem i rolami;
- create portal access, opcjonalnie z dostepem workspace.

`AutomationTriggerResource` zarzadza triggerami, a `AutomationLogResource` logami automatyzacji.

## Produktywnosc i kalendarz

`CalendarPage` to niestandardowa strona kalendarza z integracja Google Calendar. Oferuje:

- sprawdzanie polaczenia Google per uzytkownik i tenant;
- ostrzezenie, gdy token nie ma refresh tokena;
- szybkie tworzenie eventu lokalnego i opcjonalny push do Google;
- usuwanie eventu;
- przeciaganie/reschedule eventow;
- synchronizacje wszystkich niesynchronizowanych eventow do Google;
- synchronizacje pojedynczego eventu;
- import z Google z zakresem dat i pobieraniem z wielu kalendarzy;
- connect/disconnect Google Calendar.

`CalendarEventResource` zarzadza eventami jako zasob Filament.

## SaaS Billing

`PlanResource` zarzadza planami subskrypcyjnymi: slug, nazwa, opis, aktywnosc, sort order, legacy GBP pricing, price book per waluta i interwal, Stripe Price ID, limity landing pages i AI per miesiac, feature toggles (`multi_user`, `custom_domain`, `ab_testing`) oraz lista feature bullets. Tabela wspiera reorder po `sort_order`.

`BusinessResource` zarzadza tenantami: nazwa, slug, locale, timezone, aktywnosc, plan, trial, Stripe customer/subscription ID i status subskrypcji. Tabela pokazuje liczbe landing pages, plan, locale, trial i aktywnosc.

`SubscriptionResource` uzywa modelu `Business` i obsluguje widok subskrypcji w grupie SaaS Billing.

## Ustawienia systemowe

### Uzytkownicy, role, permissions i sesje

`UserResource` pozwala tworzyc i edytowac uzytkownikow panelu: name, email, phone, locale, password, aktywnosc i role. Tabela pokazuje role, aktywnosc i ostatnie logowanie.

`RoleResource` zarzadza rolami Spatie Permission. Formularz grupuje permissions w sekcje: CRM, Finance, Projects, Templates, Automations, Website, SaaS Billing, Users, Reports i System. Ma przyciski Expand All/Collapse All oraz checkbox lists z bulk toggle.

`PermissionResource` zarzadza pojedynczymi permissions. `SessionResource` pokazuje aktywne sesje i ma uprawnienie do revoke sessions.

### Platnosci

`PaymentSettingsPage` ustawia domyslna walute platnosci, Stripe (`stripe_enabled`, publishable key, secret key, webhook signing secret) i PayU (`payu_enabled`, sandbox, POS ID, OAuth2 client ID, MD5 key, client secret). Ma akcje `Test Stripe`, ktora wykonuje `Stripe\Balance::retrieve()`.

### Integracje

`IntegrationSettingsPage` konfiguruje:

- outbound mail: driver SMTP/Postmark/Resend/Mailgun/SES/log, host, port, encryption, username/API key, password/secret, from address/name;
- Twilio SMS: enabled, SID, token, sender, test number;
- domyslna marze domen;
- Openprovider: provider manual/openprovider, username, password, reseller ID, sandbox.

Akcje naglowka: Save settings, Send test email, Send test SMS, Test Openprovider connection. Test Openprovider wykonuje login do sandbox/production endpoint i czysci cache tokena przy sukcesie.

### Legal & Company

`LegalSettingsPage` zarzadza zmiennymi legalnymi: dane firmy, Companies House, VAT, adres, e-mail, telefon, ICO, privacy/GDPR contact, DPO, retencja danych, complaints, deposit, payment terms, daty i wersje dokumentow, dane prawne rejestracji domen. Zapisuje wszystko jako `Setting` w grupie `legal`.

### Tracking & Analytics

`TrackingSettingsPage` zarzadza Google Tag Manager, GA4, Meta Pixel, Google Ads i cookie consent banner. Pola obejmuja toggles oraz identyfikatory GTM, GA4, Pixel i Google Ads.

### Job Manager

`JobManagerPage` zarzadza kolejkami Laravel. Pokazuje status kolejek: pending, active, failed, worker active, last failed i rozbicie per queue. Ma taby failed jobs, pending jobs i job batches; wyszukiwanie, filtrowanie po queue, paginacje, podglad payloadow i exceptionow. Akcje:

- retry pojedynczego failed job;
- retry all;
- delete failed job;
- flush all failed;
- delete pending job;
- cancel/delete batch;
- restart workers przez `queue:restart`.

### Log Viewer

`LogViewerPage` przeglada pliki `storage/logs/*.log`. Umozliwia wybor pliku, filtrowanie po poziomie (`ALL`, `ERROR`, `WARNING`, `INFO`, `DEBUG`, `NOTICE`, `CRITICAL`, `ALERT`, `EMERGENCY`), wyszukiwanie, paginacje, refresh, download i czyszczenie wybranego logu.

### Sitemap

`SitemapPage` pokazuje URL `/sitemap.xml`. Akcja `Refresh Sitemap` czysci cache `sitemap.xml` i pingue Google oraz Bing.

## Bezpieczenstwo

`SecurityEventResource` jest w grupie System i nie dziedziczy po `BaseResource`, tylko po bezposrednim `Filament\Resources\Resource`. Pokazuje zdarzenia security z IP, statusem ban/unban, typem ataku, krajem/miastem, ISP, liczba prob, jail, flaga AbuseIPDB i czasem bana.

Filtry: status, jail, niezgloszone do AbuseIPDB. Akcje rekordu:

- odbanuj IP przez `Fail2banService`;
- zglos AbuseIPDB przez `AbuseIpdbService`;
- zbanuj ponownie.

Bulk action pozwala zglosic zaznaczone IP do AbuseIPDB.

## Raporty i powiadomienia

`ConversionReportPage` jest strona raportu konwersji w grupie Reports. `NotificationResource` pokazuje powiadomienia bazodanowe. W trasach admina istnieja eksporty raportow leadow, faktur i projektow dla formatow obslugiwanych przez `ReportController`.

Panel uzywa rowniez powiadomien Filament generowanych z kodu aplikacji, np. dla zaakceptowanej wyceny, klikniecia CTA oferty sprzedazowej i leadow z formularzy.

## Pelny inventory Filament

### Resources

| Resource | Grupa | Model / obszar |
|---|---|---|
| AutomationLogResource | Automation | AutomationLog |
| AutomationRuleResource | Automation | AutomationRule |
| AutomationTriggerResource | Automation | AutomationTrigger |
| BriefingResource | CRM | Briefing |
| BriefingTemplateResource | Sales | BriefingTemplate |
| BusinessResource | SaaS Billing | Business |
| CalculatorPricingResource | Settings | CalculatorPricing |
| CalculatorStepsResource | Settings | CalculatorStep |
| CalculatorStringsResource | Settings | CalculatorString |
| CalendarEventResource | Productivity | CalendarEvent |
| ClientResource | CRM | Client |
| ContractResource | Finance | Contract |
| ContractTemplateResource | Finance | ContractTemplate |
| DomainOrderResource | Domains | DomainOrder |
| DomainPriceListResource | Domains | DomainPriceList |
| DomainResource | Domains | Domain |
| EmailTemplateResource | Marketing | EmailTemplate |
| InvoiceResource | Finance | Invoice |
| LandingPageResource | Marketing | LandingPage |
| LeadResource | CRM | Lead |
| NavItemResource | Marketing | NavItem |
| NotificationResource | Reports | DatabaseNotification |
| PageResource | Marketing | Page |
| PaymentResource | Finance | Payment |
| PermissionResource | Settings | Spatie Permission |
| PipelineStageResource | CRM | PipelineStage |
| PlanResource | SaaS Billing | Plan |
| PortfolioProjectResource | Marketing | PortfolioProject |
| ProjectResource | Projects | Project |
| ProjectTemplateResource | Projects | ProjectTemplate |
| QuoteResource | Finance | Quote |
| RoleResource | Settings | Spatie Role |
| SalesOfferResource | CRM | SalesOffer |
| SalesOfferTemplateResource | Sales | SalesOfferTemplate |
| SecurityEventResource | System | SecurityEvent |
| ServiceItemResource | Marketing | ServiceItem |
| ServicePageResource | Marketing | ServicePage |
| SessionResource | Settings | Session |
| SiteSectionResource | Marketing | SiteSection |
| SmsTemplateResource | Marketing | SmsTemplate |
| SubscriptionResource | SaaS Billing | Business / Subscription view |
| UserResource | Settings | User |

### Pages

| Page | Grupa | Funkcja |
|---|---|---|
| AdminDashboard | Dashboard | Widgety operacyjne CRM, projekty, finanse i SaaS |
| AccountProfilePage | Konto | Profil admina, avatar, haslo, 2FA |
| CalculatorAdminPage | Settings | Zarzadzanie kalkulatorem przez widok dedykowany |
| CalendarPage | Productivity | Kalendarz i Google Calendar sync/import |
| ConversionReportPage | Reports | Raport konwersji |
| IntegrationSettingsPage | Settings | SMTP, Twilio, Openprovider, marze domen |
| JobManagerPage | Settings | Kolejki, failed jobs, pending jobs, batches |
| LegalSettingsPage | Settings | Zmienne legalne i firmowe |
| LogViewerPage | Settings | Przegladanie, pobieranie i czyszczenie logow |
| NavbarSettingsPage | Marketing | Ustawienia nawigacji i CRUD pozycji menu |
| PaymentSettingsPage | Settings | Stripe, PayU i waluta platnosci |
| PipelinePage | CRM | Kanban/lista sales pipeline i operacje na leadach |
| SitemapPage | Settings | Odwiezenie sitemap cache i ping wyszukiwarek |
| TrackingSettingsPage | Settings | GTM, GA4, Meta Pixel, Google Ads, cookie consent |

### Widgets

| Widget | Typ | Funkcja |
|---|---|---|
| ActiveProjectsWidget | TableWidget | Aktywne projekty |
| CalculatorPricingTableWidget | TableWidget | Opcje cenowe kalkulatora |
| CalculatorStepsTableWidget | TableWidget | Kroki kalkulatora |
| CalculatorStringsTableWidget | TableWidget | Stringi UI kalkulatora |
| DomainExpiryWidget | TableWidget | Domeny bliskie wygasniecia |
| DomainOrderStatsWidget | StatsOverviewWidget | Statystyki zamowien domen |
| LeadsBySourceWidget | ChartWidget | Leady wedlug zrodla |
| OverdueInvoicesWidget | TableWidget | Przeterminowane faktury |
| ProjectDeadlinesWidget | TableWidget | Deadline'y projektow |
| ProjectStatusWidget | ChartWidget | Projekty wedlug statusu |
| QuickActionsWidget | Widget | Szybkie akcje |
| RecentLeadsWidget | TableWidget | Ostatnie leady |
| RevenueChartWidget | ChartWidget | Przychody |
| SaasMetricsWidget | StatsOverviewWidget | MRR/ARR/subskrypcje |
| SecurityStatsWidget | StatsOverviewWidget | Statystyki security |
| StaleLeadsWidget | TableWidget | Leady bez follow-up |
| StatsOverviewWidget | StatsOverviewWidget | Podstawowe KPI panelu |

## Wnioski

Panel administratora jest rozbudowanym backoffice dla B2B SaaS agencji webowych. Oferuje pelne zarzadzanie CRM, klientami, leadami, pipeline, projektami, finansami, domenami, marketingiem/CMS, kalkulatorem, automatyzacjami, subskrypcjami SaaS, uzytkownikami, rolami, ustawieniami integracji, platnosciami, legal, trackingiem, kolejkami, logami i security.

Najbardziej operacyjne funkcje wykraczajace poza zwykly CRUD to: tworzenie briefingow i ofert z leadow, wysylka ofert, pipeline z konwersja lead -> projekt, Google Calendar sync/import, zarzadzanie kolejkami, testy integracji SMTP/SMS/Openprovider/Stripe, cennik domen z walutami i marzami, obsluga fail2ban/AbuseIPDB oraz zarzadzanie menu i trackingiem publicznej strony.