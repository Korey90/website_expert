# Prototypy panelu administracyjnego

## Prototyp powłoki i widoków

Otwórz `admin-ui-shell.html` bezpośrednio w przeglądarce. Plik jest samodzielny i nie wymaga uruchomionego Laravela ani Vite.

Prototyp obejmuje:

- nawigację ograniczoną do siedmiu grup;
- topbar z wyszukiwaniem, szybkim tworzeniem i zawsze widocznym workspace’em;
- dashboard operacyjny;
- listę leadów z mobilnym widokiem kart;
- formularz tworzenia leada;
- widok szczegółowy projektu;
- Pipeline z kontrolowanym przewijaniem poziomym;
- hub raportów finansowych;
- puste, ładowane, błędne i poprawne stany systemowe;
- jasny i ciemny motyw;
- układ desktopowy i mobilny.

## Bezpośrednie widoki

- `admin-ui-shell.html#dashboard`
- `admin-ui-shell.html#leads`
- `admin-ui-shell.html#new-lead`
- `admin-ui-shell.html#project`
- `admin-ui-shell.html#pipeline`
- `admin-ui-shell.html#report`
- `admin-ui-shell.html#states`

Globalne wyszukiwanie otwiera się przyciskiem w topbarze lub skrótem `Ctrl+K` / `Cmd+K`.

## Dokumentacja projektu

- architektura informacji: `docs/analysis/admin-ui-information-architecture.md`;
- plan i postęp: `docs/plans/admin-ui-redesign-plan.md`.

Prototyp nie modyfikuje danych i nie jest podłączony do produkcyjnego panelu `/admin/*`.
