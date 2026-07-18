# Current Task

**Status:** In Progress
**Last Updated:** 2026-07-18

## Zadanie
Przeanalizować faktyczny panel administratora w projekcie i zapisać pełny raport Markdown w `docs/`, bez opierania się na istniejących plikach dokumentacji.

## Plan
1. Ustalić konfigurację panelu Filament, ścieżkę, autoryzację, navigation groups i globalne elementy UI.
2. Zinwentaryzować faktyczne zasoby, strony, widgety, akcje, relacje i ustawienia widoczne w `app/Filament` oraz powiązanych klasach aplikacji.
3. Opisać funkcje panelu po domenach biznesowych wraz z ograniczeniami wynikającymi z uprawnień i multi-tenancy.
4. Zapisać raport w `docs/admin-panel-report.md` i zweryfikować, że plik istnieje oraz nie opiera się na nieaktualnych dokumentach.

## Anchor files
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Resources/*Resource.php`
- `app/Filament/Pages/*.php`
- `app/Filament/Widgets/*.php`

---
