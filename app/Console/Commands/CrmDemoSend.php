<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CrmDemoSend extends Command
{
    protected $signature   = 'crm:demo {--delay=4 : Seconds between messages}';
    protected $description = 'Send the full client communication sequence (real emails + SMS)';

    private string $email;
    private string $phone;
    private SmsService $sms;
    private int $step = 0;
    private int $delay;

    public function handle(SmsService $sms): int
    {
        $this->sms   = $sms;
        $this->email = Setting::get('mail_from', '');
        $this->phone = Setting::get('sms_test_number', '');
        $this->delay = (int) $this->option('delay');

        if (! $this->email) {
            $this->error('Brak adresu email — skonfiguruj Integration Settings w panelu.');
            return self::FAILURE;
        }
        if (! $this->phone) {
            $this->error('Brak numeru telefonu — ustaw "Test phone number" w Integration Settings.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  <fg=cyan;options=bold>CRM — Pełny workflow klienta (demo)</>');
        $this->line('  ═══════════════════════════════════════════════════');
        $this->line("  📧 Email → <fg=yellow>{$this->email}</>");
        $this->line("  📱 SMS   → <fg=yellow>{$this->phone}</>");
        $this->line("  ⏱  Przerwa między wiadomościami: {$this->delay}s");
        $this->line('  ═══════════════════════════════════════════════════');
        $this->newLine();

        // ── KROK 1: Nowy lead w systemie ────────────────────────
        $this->header('1/10', 'Nowy lead pojawia się w systemie');

        $this->sendSms(
            'Dziękujemy za kontakt z NoName Agency! Odezwiemy się w ciągu 24h. 🙌'
        );
        $this->sendEmail(
            '[CRM] 🔔 Nowy lead: Sklep internetowy – NoName Agency',
            "Nowy lead w systemie!\n\n" .
            "Klient: Jan Kowalski\n" .
            "Email: {$this->email}\n" .
            "Telefon: {$this->phone}\n" .
            "Projekt: Sklep internetowy\n" .
            "Wartość: £5,000\n" .
            "Źródło: kalkulator\n" .
            "Stage: New Lead\n\n" .
            "Zaloguj się do panelu CRM aby podjąć działania."
        );

        // ── KROK 2: Contacted ────────────────────────────────────
        $this->header('2/10', 'Nawiązanie kontaktu → stage: Contacted');

        $this->sendEmail(
            'Dziękujemy za kontakt – NoName Agency',
            "Dzień dobry Jan!\n\n" .
            "Dziękujemy za zainteresowanie naszymi usługami. Chętnie omówimy Twój projekt sklepu internetowego.\n\n" .
            "Kiedy możemy porozmawiać? Napisz do nas lub zadzwoń — jesteśmy do dyspozycji.\n\n" .
            "Z poważaniem,\nNoName Agency"
        );
        $this->sendSms(
            'Cześć Jan! Wysłaliśmy Ci maila w sprawie projektu sklepu. Sprawdź proszę skrzynkę. – NoName Agency'
        );

        // ── KROK 3: Proposal Sent ────────────────────────────────
        $this->header('3/10', 'Wysłanie oferty → stage: Proposal Sent');

        $this->sendEmail(
            'Oferta: Sklep internetowy – £5,000 netto – NoName Agency',
            "Dzień dobry Jan!\n\n" .
            "W załączeniu przesyłamy ofertę na realizację sklepu internetowego.\n\n" .
            "─────────────────────────────────\n" .
            "ZAKRES PRAC:\n" .
            "  • Projekt UI/UX (3 propozycje)\n" .
            "  • Sklep WooCommerce / własny silnik\n" .
            "  • Integracja płatności (Stripe, PayPal)\n" .
            "  • Panel administracyjny\n" .
            "  • Wdrożenie + hosting setup\n" .
            "─────────────────────────────────\n" .
            "HARMONOGRAM PŁATNOŚCI:\n" .
            "  • Zaliczka 50%: £2,500 + VAT przy podpisaniu\n" .
            "  • Płatność końcowa 50%: £2,500 + VAT przy odbiorze\n" .
            "─────────────────────────────────\n" .
            "Cena netto: £5,000\n" .
            "VAT 20%: £1,000\n" .
            "ŁĄCZNIE BRUTTO: £6,000\n" .
            "─────────────────────────────────\n" .
            "Czas realizacji: 4–6 tygodni od podpisania umowy.\n\n" .
            "Zapraszamy do kontaktu w razie pytań!\n\n" .
            "Z poważaniem,\nNoName Agency"
        );
        $this->sendSms(
            'Cześć Jan! Czy dotarła do Ciebie nasza oferta na sklep? Chętnie odpowiemy na pytania. – NoName Agency'
        );

        // ── KROK 4: Negotiation ───────────────────────────────────
        $this->header('4/10', 'Negocjacje → stage: Negotiation');

        $this->sendEmail(
            'Re: Oferta – aktualizacja warunków – NoName Agency',
            "Dzień dobry Jan!\n\n" .
            "Dziękujemy za Twój odzew i uwagi do oferty.\n\n" .
            "Jesteśmy otwarci na rozmowę o szczegółach. Proponujemy spotkanie online (30 min) " .
            "w tym lub przyszłym tygodniu — daj znać jaki termin Ci odpowiada.\n\n" .
            "Alternatywnie możemy przesłać zaktualizowaną ofertę z doprecyzowanym zakresem.\n\n" .
            "Z poważaniem,\nNoName Agency"
        );

        // ── KROK 5: Won ───────────────────────────────────────────
        $this->header('5/10', 'Umowa podpisana → stage: Won 🎉');

        $this->sendEmail(
            'Zaczynamy! Kickoff – Sklep internetowy – NoName Agency 🎉',
            "Witaj Jan!\n\n" .
            "Cieszymy się, że będziemy pracować razem nad Twoim sklepem!\n\n" .
            "KICKOFF CALL:\n" .
            "  📅 Piątek, 27 marca 2026\n" .
            "  🕙 10:00 – 10:45\n" .
            "  🔗 Google Meet: meet.google.com/abc-defg-hij\n\n" .
            "Na spotkaniu omówimy:\n" .
            "  • Szczegółowy zakres i priorytety\n" .
            "  • Dostępy (hosting, domeny, treści)\n" .
            "  • Harmonogram faz projektu\n" .
            "  • Kanał komunikacji (Slack / email)\n\n" .
            "Do zobaczenia w piątek!\n\n" .
            "Z poważaniem,\nNoName Agency"
        );
        $this->sendSms(
            'Witaj Jan! Umowa podpisana, zaczynamy! 🎉 Kickoff call w piątek 27.03 o 10:00. Szczegóły na mailu. – NoName Agency'
        );

        // ── KROK 6: Faktura zaliczkowa ────────────────────────────
        $this->header('6/10', 'Faktura zaliczkowa 50%');

        $this->sendEmail(
            'Faktura INV-2026-001 – Zaliczka 50% (£3,000.00 brutto) – NoName Agency',
            "Dzień dobry Jan!\n\n" .
            "W związku z rozpoczęciem projektu prosimy o opłacenie faktury zaliczkowej.\n\n" .
            "─────────────────────────────────\n" .
            "FAKTURA: INV-2026-001\n" .
            "Data wystawienia: 22.03.2026\n" .
            "Termin płatności: 29.03.2026\n" .
            "─────────────────────────────────\n" .
            "Zaliczka 50% za projekt: Sklep internetowy\n" .
            "Netto: £2,500.00\n" .
            "VAT 20%: £500.00\n" .
            "DO ZAPŁATY: £3,000.00\n" .
            "─────────────────────────────────\n" .
            "Dane do przelewu:\n" .
            "  Bank: Monzo Business\n" .
            "  Sort code: 04-00-03\n" .
            "  Account: 12345678\n" .
            "  Ref: INV-2026-001\n" .
            "─────────────────────────────────\n" .
            "Lub zapłać kartą online: https://pay.stripe.com/demo\n\n" .
            "Dziękujemy!\nNoName Agency"
        );

        // ── KROK 7: Potwierdzenie płatności zaliczki ──────────────
        $this->header('7/10', 'Potwierdzenie płatności zaliczki');

        $this->sendEmail(
            'Potwierdzenie płatności – INV-2026-001 – Dziękujemy! ✅',
            "Dzień dobry Jan!\n\n" .
            "Otrzymaliśmy Twoją płatność. Dziękujemy!\n\n" .
            "─────────────────────────────────\n" .
            "Faktura: INV-2026-001\n" .
            "Kwota: £3,000.00\n" .
            "Data płatności: 22.03.2026\n" .
            "Status: OPŁACONA ✅\n" .
            "─────────────────────────────────\n" .
            "Projekt startuje pełną parą! Trzymaj się naszego kalendarza — " .
            "pierwsze efekty zobaczysz już po fazie Discovery & Brief.\n\n" .
            "Z poważaniem,\nNoName Agency"
        );
        $this->sendSms(
            'Jan, otrzymaliśmy płatność £3,000 za INV-2026-001. Dziękujemy! Projekt startuje. 🚀 – NoName Agency'
        );

        // ── KROK 8: Projekt ukończony ─────────────────────────────
        $this->header('8/10', 'Realizacja projektu ukończona – wszystkie fazy ✔');

        $this->sendEmail(
            'Projekt gotowy do odbioru! 🎉 Sklep internetowy – NoName Agency',
            "Dzień dobry Jan!\n\n" .
            "Wszystkie fazy projektu zostały ukończone i sklep jest gotowy do odbioru!\n\n" .
            "─────────────────────────────────\n" .
            "FAZY PROJEKTU:\n" .
            "  ✅ Discovery & Strategy\n" .
            "  ✅ Design UI/UX\n" .
            "  ✅ Development\n" .
            "  ✅ Testing & QA\n" .
            "  ✅ Launch & Handover\n" .
            "─────────────────────────────────\n" .
            "DOSTĘPY:\n" .
            "  🌐 Strona: https://twojsklep.pl\n" .
            "  🔐 Panel admin: https://twojsklep.pl/wp-admin\n" .
            "  📁 Dane dostępowe: w osobnym mailu zaszyfrowanym\n" .
            "─────────────────────────────────\n" .
            "Prosimy o sesję odbioru (45 min) — zapraszamy do umówienia terminu.\n\n" .
            "Z poważaniem,\nNoName Agency"
        );

        // ── KROK 9: Faktura końcowa ───────────────────────────────
        $this->header('9/10', 'Faktura końcowa 50%');

        $this->sendEmail(
            'Faktura INV-2026-002 – Płatność końcowa (£3,000.00 brutto) – NoName Agency',
            "Dzień dobry Jan!\n\n" .
            "Projekt został ukończony — przesyłamy fakturę końcową.\n\n" .
            "─────────────────────────────────\n" .
            "FAKTURA: INV-2026-002\n" .
            "Data wystawienia: 22.03.2026\n" .
            "Termin płatności: 05.04.2026\n" .
            "─────────────────────────────────\n" .
            "Płatność końcowa 50% za projekt: Sklep internetowy\n" .
            "Netto: £2,500.00\n" .
            "VAT 20%: £500.00\n" .
            "DO ZAPŁATY: £3,000.00\n" .
            "─────────────────────────────────\n" .
            "Lub zapłać kartą online: https://pay.stripe.com/demo\n\n" .
            "Dziękujemy za współpracę!\nNoName Agency"
        );
        $this->sendSms(
            'Jan, Twój sklep jest gotowy! 🛒 Faktura końcowa INV-2026-002 (£3,000) w mailu. Dziękujemy za współpracę! – NoName Agency'
        );

        // ── KROK 10: Zamknięcie projektu ─────────────────────────
        $this->header('10/10', 'Zamknięcie projektu – podsumowanie');

        $this->sendEmail(
            '🏁 Projekt zamknięty – dostępy, gwarancja i co dalej – NoName Agency',
            "Dzień dobry Jan!\n\n" .
            "Projekt 'Sklep internetowy' został oficjalnie ukończony i przekazany. " .
            "To był świetny projekt — dziękujemy za zaufanie!\n\n" .
            "─────────────────────────────────\n" .
            "PODSUMOWANIE PROJEKTU:\n" .
            "  Projekt: Sklep internetowy\n" .
            "  Data startu: 22.03.2026\n" .
            "  Data ukończenia: 22.03.2026\n" .
            "  Łączna wartość: £6,000 brutto\n" .
            "─────────────────────────────────\n" .
            "GWARANCJA I WSPARCIE:\n" .
            "  • 3 miesiące bezpłatnych poprawek\n" .
            "  • Wsparcie techniczne: support@noname.agency\n" .
            "  • Dokumentacja w załączniku\n" .
            "─────────────────────────────────\n" .
            "CO DALEJ?\n" .
            "  Jeśli w przyszłości będziesz potrzebować rozbudowy sklepu, kampanii " .
            "marketingowej lub innego projektu — jesteśmy tu dla Ciebie.\n\n" .
            "Będziemy wdzięczni za opinię Google: https://g.page/noname-agency/review\n\n" .
            "Życzę wielu zamówień! 🛒\n\n" .
            "Z poważaniem,\nKonrad\nNoName Agency"
        );
        $this->sendSms(
            'Jan, dziękujemy! 🙏 Twój sklep działa. Zostaw nam opinię Google: https://g.page/noname-agency/review — NoName Agency'
        );

        // ── PODSUMOWANIE ──────────────────────────────────────────
        $this->newLine();
        $this->line('  ═══════════════════════════════════════════════════');
        $this->line('  <fg=green;options=bold>GOTOWE! Cały workflow wysłany.</>');
        $this->line('  ═══════════════════════════════════════════════════');
        $this->line("  📧 Emaile wysłane na: {$this->email}");
        $this->line("  📱 SMS-y wysłane na: {$this->phone}");
        $this->newLine();

        return self::SUCCESS;
    }

    private function header(string $step, string $title): void
    {
        $this->newLine();
        $this->line("  <fg=cyan>┌─── [{$step}] {$title}</>  ");
    }

    private function sendEmail(string $subject, string $body): void
    {
        $this->line("  <fg=white>│</> ✉  EMAIL  <fg=yellow>{$this->email}</>");
        $this->line("  <fg=white>│</>    Subject: \"{$subject}\"");
        try {
            Mail::raw($body, function ($msg) use ($subject) {
                $msg->to($this->email)->subject($subject);
            });
            $this->line('  <fg=white>│</> <fg=green>   ✔ Wysłany</>');
        } catch (\Throwable $e) {
            $this->line("  <fg=white>│</> <fg=red>   ✘ BŁĄD: {$e->getMessage()}</>");
        }
        $this->pause();
    }

    private function sendSms(string $message): void
    {
        $chars = strlen($message);
        $segments = (int) ceil($chars / 160);
        $this->line("  <fg=white>│</> 📱 SMS    <fg=yellow>{$this->phone}</>  [{$chars} chars / {$segments} SMS]");
        $this->line("  <fg=white>│</>    \"{$message}\"");
        $ok = $this->sms->send($this->phone, $message);
        if ($ok) {
            $this->line('  <fg=white>│</> <fg=green>   ✔ Wysłany</>');
        } else {
            $this->line('  <fg=white>│</> <fg=red>   ✘ BŁĄD — sprawdź storage/logs/laravel.log</>');
        }
        $this->pause();
    }

    private function pause(): void
    {
        if ($this->delay > 0) {
            sleep($this->delay);
        }
    }
}
