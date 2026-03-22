<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pełny test workflow CRM – od nowego leada do zamknięcia projektu.
 *
 * Klient testowy:
 *   e-mail : 20noname22x@gmail.com
 *   telefon: +447882799050
 *
 * Uruchomienie:
 *   php artisan test --filter=FullLeadWorkflowTest
 */
class FullLeadWorkflowTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────────────────────────
    // Shared fixtures
    // ────────────────────────────────────────────────────────────────
    private User   $manager;
    private Client $client;

    // Captured outbox for T8 summary
    private array $outboxEmails = [];
    private array $outboxSms    = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->manager = User::factory()->create([
            'name'  => 'Test Manager',
            'email' => 'manager.workflow.test@local.test',
        ]);
        $this->manager->assignRole('manager');

        $this->client = Client::create([
            'company_name'          => 'NoName Agency Ltd',
            'primary_contact_name'  => 'Jan Kowalski',
            'primary_contact_email' => '20noname22x@gmail.com',
            'primary_contact_phone' => '+447882799050',
            'status'                => 'prospect',
            'source'                => 'website',
            'country'               => 'GB',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Console output helpers
    // ═══════════════════════════════════════════════════════════════

    private function out(string $line = ''): void
    {
        fwrite(STDOUT, $line . "\n");
    }

    private function sep(string $label = ''): void
    {
        $line = $label !== ''
            ? "\n  ┌─── {$label} " . str_repeat('─', max(0, 54 - mb_strlen($label))) . '┐'
            : "\n  └" . str_repeat('─', 60) . '┘';
        fwrite(STDOUT, $line . "\n");
    }

    private function step(string $msg): void
    {
        fwrite(STDOUT, "  │  ▶  {$msg}\n");
    }

    private function ok(string $msg): void
    {
        fwrite(STDOUT, "  │     ✔  {$msg}\n");
    }

    private function logEmail(string $to, string $subject, string $body = ''): void
    {
        $this->outboxEmails[] = compact('to', 'subject', 'body');
        fwrite(STDOUT, "  │     ✉  EMAIL  → {$to}\n");
        fwrite(STDOUT, "  │        Subject: \"{$subject}\"\n");
        if ($body !== '') {
            $preview = mb_strlen($body) > 110 ? mb_substr($body, 0, 110) . '…' : $body;
            fwrite(STDOUT, "  │        Body:    {$preview}\n");
        }
    }

    private function logSms(string $to, string $message): void
    {
        $this->outboxSms[] = compact('to', 'message');
        $chars    = mb_strlen($message);
        $segments = (int) ceil($chars / 160);
        fwrite(STDOUT, "  │     📱 SMS    → {$to}  [{$chars} chars / {$segments} SMS]\n");
        fwrite(STDOUT, "  │        \"{$message}\"\n");
    }

    private function logStage(string $from, string $to): void
    {
        fwrite(STDOUT, "  │     ↗  Stage:  {$from}  →  {$to}\n");
    }

    private function logInvoice(string $number, float $subtotal, float $vat, float $total, string $status): void
    {
        fwrite(STDOUT, "  │     🧾 Invoice {$number}\n");
        fwrite(STDOUT, "  │        Net £" . number_format($subtotal, 2)
            . "  +VAT £" . number_format($vat, 2)
            . "  = £" . number_format($total, 2)
            . "  [{$status}]\n");
    }

    private function logPhase(string $name, string $status): void
    {
        $icon = $status === 'completed' ? '✔' : ($status === 'active' ? '►' : '○');
        fwrite(STDOUT, "  │     {$icon}  Phase:  {$name}  [{$status}]\n");
    }

    private function dumpOutbox(): void
    {
        fwrite(STDOUT, "\n  ╔══════════════════════════════════════════════════════════╗\n");
        fwrite(STDOUT,   "  ║              OUTBOX SUMMARY (T8 E2E)                    ║\n");
        fwrite(STDOUT,   "  ╚══════════════════════════════════════════════════════════╝\n");

        fwrite(STDOUT, "\n  📧  E-MAILS SENT (" . count($this->outboxEmails) . ")\n");
        foreach ($this->outboxEmails as $i => $m) {
            $n = $i + 1;
            fwrite(STDOUT, "  [{$n}] TO: {$m['to']}\n");
            fwrite(STDOUT, "      SUBJECT: \"{$m['subject']}\"\n");
            if ($m['body']) {
                fwrite(STDOUT, "      BODY: " . mb_substr($m['body'], 0, 120) . "\n");
            }
        }

        fwrite(STDOUT, "\n  📱  SMS MESSAGES SENT (" . count($this->outboxSms) . ")\n");
        foreach ($this->outboxSms as $i => $s) {
            $n = $i + 1;
            $chars    = mb_strlen($s['message']);
            $segments = (int) ceil($chars / 160);
            fwrite(STDOUT, "  [{$n}] TO: {$s['to']}  [{$chars} chars / {$segments} SMS]\n");
            fwrite(STDOUT, "      \"{$s['message']}\"\n");
        }

        fwrite(STDOUT, "\n");
    }

    // ═══════════════════════════════════════════════════════════════
    // T1 – Tworzenie nowego leada
    // ═══════════════════════════════════════════════════════════════
    public function test_T1_nowy_lead_zostaje_prawidlowo_zapisany(): void
    {
        $this->sep('T1 — Nowy Lead');

        $stage = PipelineStage::where('slug', 'new-lead')->firstOrFail();
        $this->step("Tworzę lead: 'Strona wizytówkowa – NoName Agency'");

        $lead = Lead::create([
            'title'               => 'Strona wizytówkowa – NoName Agency',
            'client_id'           => $this->client->id,
            'pipeline_stage_id'   => $stage->id,
            'assigned_to'         => $this->manager->id,
            'value'               => 2500.00,
            'source'              => 'contact_form',
            'expected_close_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->ok("Lead ID={$lead->id} zapisany w DB");
        $this->ok("Stage: {$stage->name}");
        $this->ok("Wartość: £2,500.00  |  Expected close: " . now()->addDays(30)->toDateString());
        $this->ok("Klient: {$lead->client->primary_contact_name} <{$lead->client->primary_contact_email}>");
        $this->ok("Telefon: {$lead->client->primary_contact_phone}");

        $this->assertDatabaseHas('leads', [
            'id'                => $lead->id,
            'pipeline_stage_id' => $stage->id,
            'client_id'         => $this->client->id,
        ]);

        $this->assertEquals('20noname22x@gmail.com', $lead->client->primary_contact_email);
        $this->assertEquals('+447882799050',         $lead->client->primary_contact_phone);

        LeadActivity::log($lead->id, 'created', 'Lead created', [], $this->manager->id);
        $this->step("Log aktywności: 'created'");

        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'created']);
        $this->ok("Aktywność 'created' zapisana w DB");

        // Auto-SMS symulacja przy nowym leadzie
        $this->logSms(
            $this->client->primary_contact_phone,
            "Dziękujemy za kontakt z NoName Agency! Odezwiemy się w ciągu 24h."
        );
        $this->logEmail(
            'manager.workflow.test@local.test',
            "🔔 Nowy lead: {$lead->title}",
            "Lead ID={$lead->id} | Wartość: £2,500 | Źródło: contact_form | Klient: Jan Kowalski"
        );

        $this->sep();
    }

    // ═══════════════════════════════════════════════════════════════
    // T2 – Pełny lejek sprzedażowy (Won path)
    // ═══════════════════════════════════════════════════════════════
    public function test_T2_lead_przechodzi_przez_wszystkie_etapy_az_do_won(): void
    {
        $this->sep('T2 — Funnel: New Lead → Won');

        $stages = PipelineStage::orderBy('order')->get()->keyBy('slug');

        $lead = Lead::create([
            'title'             => 'Full Workflow Won Lead',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $stages['new-lead']->id,
            'assigned_to'       => $this->manager->id,
            'value'             => 3000.00,
            'source'            => 'referral',
        ]);
        $this->step("Lead '{$lead->title}' utworzony | Stage: New Lead");

        $funnel = ['new-lead', 'contacted', 'proposal-sent', 'negotiation', 'won'];
        $prev   = null;

        foreach ($funnel as $slug) {
            $stage   = $stages[$slug];
            $updates = ['pipeline_stage_id' => $stage->id];

            if ($slug === 'won') {
                $updates['won_at'] = now();
            }

            $lead->update($updates);
            $lead->refresh();

            if ($prev !== null) {
                $this->logStage($prev, $stage->name);
                LeadActivity::log(
                    $lead->id,
                    'stage_moved',
                    "Stage: {$prev} → {$stage->name}",
                    ['from' => $prev, 'to' => $stage->name],
                    $this->manager->id,
                );
            } else {
                $this->ok("Start stage: {$stage->name}");
            }

            $this->assertEquals($stage->id, $lead->pipeline_stage_id,
                "Lead powinien być w stage: {$stage->name}");

            $prev = $stage->name;
        }

        $this->ok("won_at ustawiony: " . $lead->won_at->format('Y-m-d H:i:s'));

        $stageMoves = LeadActivity::where('lead_id', $lead->id)->where('type', 'stage_moved')->count();
        $this->ok("Aktywności stage_moved w DB: {$stageMoves}");

        $this->assertEquals('won', $lead->stage->slug);
        $this->assertNotNull($lead->won_at);
        $this->assertEquals(4, $stageMoves, 'Oczekiwano 4 wpisów stage_moved');

        $this->sep();
    }

    // ═══════════════════════════════════════════════════════════════
    // T3 – Aktywności: e-mail i SMS z leada
    // ═══════════════════════════════════════════════════════════════
    public function test_T3_aktywnosci_email_i_sms_sa_logowane(): void
    {
        $this->sep('T3 — Email & SMS z leada');

        $stage = PipelineStage::where('slug', 'contacted')->firstOrFail();
        $lead  = Lead::create([
            'title'             => 'Communication Log Test',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $stage->id,
            'source'            => 'social_media',
        ]);
        $this->step("Lead ID={$lead->id} | Stage: Contacted");

        $emailSubject = 'Dziękujemy za kontakt!';
        $emailBody    = 'Dzień dobry Jan, dziękujemy za kontakt z NoName Agency. Chętnie omówimy Twój projekt.';
        $smsContent   = 'Cześć Jan! Dziękujemy za zapytanie. Odezwiemy się dziś do 17:00. – NoName Agency';

        $this->step("Wysyłam e-mail do klienta...");
        $this->logEmail($this->client->primary_contact_email, $emailSubject, $emailBody);

        LeadActivity::log(
            $lead->id, 'email_sent',
            "Email sent to {$this->client->primary_contact_email}: \"{$emailSubject}\"",
            ['to' => $this->client->primary_contact_email, 'subject' => $emailSubject],
            $this->manager->id,
        );
        $this->ok("Log 'email_sent' zapisany");

        $this->step("Wysyłam SMS do klienta...");
        $this->logSms($this->client->primary_contact_phone, $smsContent);

        LeadActivity::log(
            $lead->id, 'sms_sent',
            "SMS sent to {$this->client->primary_contact_phone}: \"{$smsContent}\"",
            ['to' => $this->client->primary_contact_phone, 'chars' => mb_strlen($smsContent)],
            $this->manager->id,
        );
        $this->ok("Log 'sms_sent' zapisany");

        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'email_sent']);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'sms_sent']);

        $activities = LeadActivity::where('lead_id', $lead->id)->get();
        $this->assertCount(2, $activities);
        $this->ok("Łącznie {$activities->count()} aktywności w DB");

        $this->sep();
    }

    // ═══════════════════════════════════════════════════════════════
    // T4 – Konwersja leada na projekt (nie ma prefixu test_ bo T4 brak)
    // ═══════════════════════════════════════════════════════════════
    public function test_T4_lead_won_zamieniony_na_projekt(): void
    {
        $this->sep('T4 — Lead Won → Projekt');

        $stageWon = PipelineStage::where('slug', 'won')->firstOrFail();
        $template = ProjectTemplate::where('service_type', 'wizytowka')->first();

        $lead = Lead::create([
            'title'             => 'Wizytówka do konwersji',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $stageWon->id,
            'assigned_to'       => $this->manager->id,
            'value'             => 2000.00,
            'source'            => 'referral',
            'won_at'            => now(),
        ]);
        $this->step("Lead Won: '{$lead->title}' | £2,000");

        $project = Project::create([
            'title'       => $lead->title,
            'client_id'   => $this->client->id,
            'lead_id'     => $lead->id,
            'template_id' => $template?->id,
            'assigned_to' => $this->manager->id,
            'status'      => 'active',
            'budget'      => $lead->value,
            'currency'    => 'GBP',
            'start_date'  => now()->toDateString(),
            'deadline'    => now()->addDays(30)->toDateString(),
        ]);
        $this->ok("Projekt ID={$project->id} | Template: " . ($template?->name ?? 'brak'));
        $this->ok("portal_token: " . substr($project->portal_token, 0, 16) . '…(' . strlen($project->portal_token) . ' chars)');
        $this->ok("Deadline: " . now()->addDays(30)->toDateString());

        LeadActivity::log(
            $lead->id, 'project_created',
            "Project created: {$project->title}",
            ['project_id' => $project->id],
            $this->manager->id,
        );
        $this->step("Log 'project_created' zapisany");

        $this->logEmail(
            $this->client->primary_contact_email,
            "Kickoff: projekt '{$project->title}' ruszył!",
            "Dzień dobry Jan! Projekt startuje " . now()->toDateString() . ". Deadline: " . now()->addDays(30)->toDateString() . ". Do zobaczenia na kickoffie!"
        );

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'active', 'lead_id' => $lead->id]);
        $this->assertNotNull($project->portal_token);
        $this->assertEquals(64, strlen($project->portal_token));
        $this->assertEquals($project->id, $lead->fresh()->project->id);

        $this->sep();
    }

    // ═══════════════════════════════════════════════════════════════
    // T5 – Fakturowanie
    // ═══════════════════════════════════════════════════════════════
    public function test_T5_faktura_zaliczkowa_zostaje_stworzona_i_oplacona(): void
    {
        $this->sep('T5 — Fakturowanie');

        $stageWon = PipelineStage::where('slug', 'won')->firstOrFail();
        $lead = Lead::create([
            'title'             => 'Invoice Test Lead',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $stageWon->id,
            'value'             => 2500.00,
            'source'            => 'other',
            'won_at'            => now(),
        ]);

        $project = Project::create([
            'title'     => $lead->title,
            'client_id' => $this->client->id,
            'lead_id'   => $lead->id,
            'status'    => 'active',
            'budget'    => 2500.00,
            'currency'  => 'GBP',
        ]);
        $this->step("Projekt ID={$project->id} | Budget: £2,500");

        // Faktura zaliczkowa 50%
        $this->step("Tworzę fakturę zaliczkową 50%...");
        $invoice = Invoice::create([
            'number'     => 'INV-TEST-50PCT-001',
            'client_id'  => $this->client->id,
            'project_id' => $project->id,
            'created_by' => $this->manager->id,
            'status'     => 'draft',
            'currency'   => 'GBP',
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(14)->toDateString(),
            'vat_rate'   => 20,
        ]);

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'description' => 'Zaliczka 50% – Strona wizytówkowa',
            'quantity'    => 1,
            'unit_price'  => 1250.00,
            'order'       => 1,
        ]);

        $invoice->recalculate();
        $invoice->refresh();

        $this->logInvoice($invoice->number, (float)$invoice->subtotal, (float)$invoice->vat_amount, (float)$invoice->total, 'draft');

        $this->assertEquals(1250.00, (float) $invoice->subtotal);
        $this->assertEquals(250.00,  (float) $invoice->vat_amount);
        $this->assertEquals(1500.00, (float) $invoice->total);

        // Wysyłka
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        $invoice->refresh();
        $this->ok("Faktura wysłana do klienta [{$invoice->status}]");
        $this->logEmail(
            $this->client->primary_contact_email,
            "Faktura {$invoice->number} – Zaliczka 50% (£1,500.00 brutto)",
            "Prosimy o opłacenie faktury {$invoice->number} na kwotę £1,500 do " . now()->addDays(14)->toDateString() . "."
        );

        $this->assertEquals('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);
        $this->assertFalse($invoice->isOverdue());

        // Opłacenie
        $invoice->update(['status' => 'paid', 'paid_at' => now(), 'amount_paid' => 1500.00, 'amount_due' => 0.00]);
        $invoice->refresh();
        $this->ok("Faktura opłacona [{$invoice->status}] | paid_at: " . $invoice->paid_at->format('Y-m-d H:i:s'));
        $this->logEmail(
            $this->client->primary_contact_email,
            "Potwierdzenie płatności – {$invoice->number}",
            "Dziękujemy! Otrzymaliśmy płatność £1,500 za fakturę {$invoice->number}. Projekt ruszamy pełną parą!"
        );

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertFalse($invoice->isOverdue());

        $this->sep();
    }

    // ═══════════════════════════════════════════════════════════════
    // T6 – Fazy projektu i zamknięcie
    // ═══════════════════════════════════════════════════════════════
    public function test_T6_projekt_przechodzi_przez_fazy_i_zostaje_ukonczony(): void
    {
        $this->sep('T6 — Fazy projektu');

        $stageWon = PipelineStage::where('slug', 'won')->firstOrFail();
        $lead = Lead::create([
            'title'             => 'Phases Test Lead',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $stageWon->id,
            'source'            => 'other',
            'won_at'            => now(),
        ]);

        $project = Project::create([
            'title'      => $lead->title,
            'client_id'  => $this->client->id,
            'lead_id'    => $lead->id,
            'status'     => 'active',
            'currency'   => 'GBP',
            'start_date' => now()->toDateString(),
            'deadline'   => now()->addDays(30)->toDateString(),
        ]);
        $this->step("Projekt ID={$project->id} | 5 faz");

        $phaseNames = ['Discovery & Brief', 'Design Mockups', 'Development', 'Testing & QA', 'Launch & Handover'];
        foreach ($phaseNames as $i => $name) {
            ProjectPhase::create([
                'project_id' => $project->id,
                'name'       => $name,
                'order'      => $i + 1,
                'status'     => 'pending',
            ]);
        }

        $project->load('phases');
        $this->assertCount(5, $project->phases);

        foreach ($project->phases as $phase) {
            $phase->update(['status' => 'active']);
            $this->logPhase($phase->name, 'active');

            $phase->update(['status' => 'completed']);
            $this->logPhase($phase->name, 'completed');
            $this->assertEquals('completed', $phase->fresh()->status);
        }

        $this->step("Zamykam projekt...");
        $project->update(['status' => 'completed', 'completed_at' => now()]);
        $project->refresh();

        $this->ok("Projekt [{$project->status}] | completed_at: " . $project->completed_at->format('Y-m-d H:i:s'));
        $this->logEmail(
            $this->client->primary_contact_email,
            "Projekt '{$project->title}' gotowy do odbioru! 🎉",
            "Dzień dobry Jan! Wszystkie 5 faz zostały ukończone. Zapraszamy na sesję odbioru. Dostępy do strony w załączeniu."
        );

        $this->assertEquals('completed', $project->status);
        $this->assertNotNull($project->completed_at);

        $this->sep();
    }

    // ═══════════════════════════════════════════════════════════════
    // T7 – Ścieżka Lost
    // ═══════════════════════════════════════════════════════════════
    public function test_T7_lead_zostaje_oznaczony_jako_lost(): void
    {
        $this->sep('T7 — Lead Lost');

        $stageProposal = PipelineStage::where('slug', 'proposal-sent')->firstOrFail();
        $stageLost     = PipelineStage::where('slug', 'lost')->firstOrFail();

        $lead = Lead::create([
            'title'             => 'Lost Lead Test',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $stageProposal->id,
            'value'             => 8000.00,
            'source'            => 'cold_outreach',
        ]);
        $this->step("Lead '{$lead->title}' | £8,000 | Stage: Proposal Sent");

        $lostReason = 'Cena powyżej budżetu klienta';
        $lead->update([
            'pipeline_stage_id' => $stageLost->id,
            'lost_at'           => now(),
            'lost_reason'       => $lostReason,
        ]);
        $lead->refresh();

        $this->logStage('Proposal Sent', 'Lost');
        $this->ok("Powód: {$lostReason}");
        $this->ok("lost_at: " . $lead->lost_at->format('Y-m-d H:i:s'));

        LeadActivity::log($lead->id, 'marked_lost', "Lead marked as Lost: {$lostReason}", ['reason' => $lostReason], $this->manager->id);
        $this->step("Log 'marked_lost' zapisany");

        $closingBody = "Dzień dobry Jan, dziękujemy za rozważenie naszej oferty. Będziemy wdzięczni za kontakt w przyszłości, gdy budżet pozwoli. Trzymamy się ciepło!";
        $this->logEmail($this->client->primary_contact_email, 'Dziękujemy za rozmowę – NoName Agency', $closingBody);
        $this->logSms($this->client->primary_contact_phone, 'Dziękujemy za rozmowę! Mamy nadzieję na współpracę w przyszłości. – NoName Agency');

        LeadActivity::log($lead->id, 'email_sent', 'Email zamykający wysłany', ['to' => $this->client->primary_contact_email, 'subject' => 'Dziękujemy za rozmowę'], $this->manager->id);

        $this->assertEquals($stageLost->id,   $lead->pipeline_stage_id);
        $this->assertEquals($lostReason,       $lead->lost_reason);
        $this->assertNotNull($lead->lost_at);

        $activities = LeadActivity::where('lead_id', $lead->id)->get();
        $this->assertTrue($activities->where('type', 'marked_lost')->isNotEmpty());
        $this->assertTrue($activities->where('type', 'email_sent')->isNotEmpty());

        $this->ok("Aktywności: " . $activities->pluck('type')->implode(', '));

        $this->sep();
    }

    // ═══════════════════════════════════════════════════════════════
    // T8 – Pełny E2E: New Lead → Won → Projekt → Faktury → Ukończenie
    // ═══════════════════════════════════════════════════════════════
    public function test_T8_pelny_workflow_e2e_new_lead_do_ukonczenia_projektu(): void
    {
        $this->outboxEmails = [];
        $this->outboxSms    = [];

        $this->sep('T8 — PEŁNY E2E WORKFLOW');
        $stages = PipelineStage::orderBy('order')->get()->keyBy('slug');

        // ── 1. New Lead ──────────────────────────────────────────────
        $this->step('[1/10] Nowy lead pojawia się w systemie');

        $lead = Lead::create([
            'title'               => 'E2E: Sklep internetowy – NoName Agency',
            'client_id'           => $this->client->id,
            'pipeline_stage_id'   => $stages['new-lead']->id,
            'assigned_to'         => $this->manager->id,
            'value'               => 5000.00,
            'source'              => 'calculator',
            'expected_close_date' => now()->addDays(21)->toDateString(),
        ]);
        LeadActivity::log($lead->id, 'created', 'Lead created', [], $this->manager->id);
        $this->ok("Lead ID={$lead->id} | £5,000 | Klient: {$lead->client->primary_contact_name}");
        $this->logSms($this->client->primary_contact_phone, "Dziękujemy za zapytanie! Odezwiemy się w ciągu 24h. – NoName Agency");
        $this->logEmail('manager.workflow.test@local.test', "🔔 Nowy lead: {$lead->title}", "£5,000 | Źródło: kalkulator | Assigned do: Test Manager");

        $this->assertEquals('20noname22x@gmail.com', $lead->client->primary_contact_email);

        // ── 2. Contacted ─────────────────────────────────────────────
        $this->step('[2/10] Nawiązanie kontaktu → stage: Contacted');

        $lead->update(['pipeline_stage_id' => $stages['contacted']->id]);
        $this->logStage('New Lead', 'Contacted');
        LeadActivity::log($lead->id, 'stage_moved', 'New Lead → Contacted', ['from' => 'New Lead', 'to' => 'Contacted'], $this->manager->id);

        $this->logEmail($this->client->primary_contact_email, 'Dziękujemy za kontakt – NoName Agency',
            'Dzień dobry Jan! Chętnie omówimy Twój projekt sklepu internetowego. Kiedy możemy porozmawiać?');
        LeadActivity::log($lead->id, 'email_sent', "Email sent to {$this->client->primary_contact_email}: \"Dziękujemy za kontakt\"", ['to' => $this->client->primary_contact_email], $this->manager->id);

        $this->logSms($this->client->primary_contact_phone, "Cześć Jan! Wysłaliśmy Ci maila w sprawie projektu. Sprawdź proszę skrzynkę. – NoName Agency");
        LeadActivity::log($lead->id, 'sms_sent', "SMS sent to {$this->client->primary_contact_phone}: \"Sprawdź skrzynkę\"", ['to' => $this->client->primary_contact_phone], $this->manager->id);

        // ── 3. Proposal Sent ─────────────────────────────────────────
        $this->step('[3/10] Wysłana oferta → stage: Proposal Sent');

        $lead->update(['pipeline_stage_id' => $stages['proposal-sent']->id, 'value' => 5000.00]);
        $this->logStage('Contacted', 'Proposal Sent');
        LeadActivity::log($lead->id, 'stage_moved', 'Contacted → Proposal Sent', [], $this->manager->id);

        $this->logEmail($this->client->primary_contact_email, 'Oferta: Sklep internetowy – £5,000 netto',
            'W załączeniu przesyłamy ofertę na realizację sklepu internetowego dla NoName Agency. Budżet: £5,000 netto + VAT. Oczekujemy na odpowiedź do ' . now()->addDays(5)->toDateString() . '.');
        LeadActivity::log($lead->id, 'email_sent', "Email sent to {$this->client->primary_contact_email}: \"Oferta na sklep\"", ['to' => $this->client->primary_contact_email], $this->manager->id);

        // follow-up SMS po 3 dniach (symulacja)
        $this->logSms($this->client->primary_contact_phone, "Cześć Jan! Czy dotarła do Ciebie nasza oferta na sklep? Chętnie odpowiemy na pytania. – NoName Agency");

        // ── 4. Negotiation ───────────────────────────────────────────
        $this->step('[4/10] Negocjacje → stage: Negotiation');

        $lead->update([
            'pipeline_stage_id'   => $stages['negotiation']->id,
            'expected_close_date' => now()->addDays(7)->toDateString(),
        ]);
        $this->logStage('Proposal Sent', 'Negotiation');
        LeadActivity::log($lead->id, 'stage_moved', 'Proposal Sent → Negotiation', [], $this->manager->id);
        $this->ok("Nowy Expected Close: " . now()->addDays(7)->toDateString());

        // ── 5. Won ───────────────────────────────────────────────────
        $this->step('[5/10] Umowa podpisana → stage: Won');

        $lead->update(['pipeline_stage_id' => $stages['won']->id, 'won_at' => now()]);
        $this->logStage('Negotiation', 'Won 🎉');
        LeadActivity::log($lead->id, 'marked_won', 'Lead marked as Won', [], $this->manager->id);
        $lead->refresh();

        $this->ok("won_at: " . $lead->won_at->format('Y-m-d H:i:s'));

        $this->logEmail($this->client->primary_contact_email, "Zaczynamy! Kickoff – Sklep internetowy",
            'Witaj Jan! Cieszymy się, że będziemy pracować razem. Kickoff call w piątek o 10:00. Do zobaczenia!');
        LeadActivity::log($lead->id, 'email_sent', "Email sent: Kickoff", ['to' => $this->client->primary_contact_email], $this->manager->id);

        $this->assertNotNull($lead->won_at);
        $this->assertEquals('won', $lead->stage->slug);

        // ── 6. Projekt ───────────────────────────────────────────────
        $this->step('[6/10] Tworzę projekt z szablonu E-Commerce');

        $template = ProjectTemplate::where('service_type', 'ecommerce')->first();
        $project  = Project::create([
            'title'       => 'Sklep internetowy – NoName Agency',
            'client_id'   => $this->client->id,
            'lead_id'     => $lead->id,
            'template_id' => $template?->id,
            'assigned_to' => $this->manager->id,
            'status'      => 'active',
            'budget'      => 5000.00,
            'currency'    => 'GBP',
            'start_date'  => now()->toDateString(),
            'deadline'    => now()->addDays(60)->toDateString(),
        ]);
        LeadActivity::log($lead->id, 'project_created', "Project created: {$project->title}", ['project_id' => $project->id], $this->manager->id);

        $this->ok("Projekt ID={$project->id} | Template: " . ($template?->name ?? 'brak'));
        $this->ok("portal_token: " . substr($project->portal_token, 0, 16) . '… (64 chars)');
        $this->assertNotNull($project->portal_token);

        // ── 7. Faktura zaliczkowa 50% ────────────────────────────────
        $this->step('[7/10] Faktura zaliczkowa 50%');

        $invoice1 = Invoice::create([
            'number'     => 'INV-E2E-001',
            'client_id'  => $this->client->id,
            'project_id' => $project->id,
            'created_by' => $this->manager->id,
            'status'     => 'sent',
            'currency'   => 'GBP',
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(7)->toDateString(),
            'vat_rate'   => 20,
            'sent_at'    => now(),
        ]);
        InvoiceItem::create(['invoice_id' => $invoice1->id, 'description' => 'Zaliczka 50% – Sklep internetowy', 'quantity' => 1, 'unit_price' => 2500.00, 'order' => 1]);
        $invoice1->recalculate();
        $invoice1->refresh();

        $this->logInvoice($invoice1->number, (float)$invoice1->subtotal, (float)$invoice1->vat_amount, (float)$invoice1->total, 'sent');
        $this->logEmail($this->client->primary_contact_email,
            "Faktura {$invoice1->number} – Zaliczka 50% (£" . number_format((float)$invoice1->total, 2) . " brutto)",
            "Prosimy o opłacenie faktury zaliczkowej do " . now()->addDays(7)->toDateString() . ". Link do płatności: https://pay.stripe.com/xxx"
        );

        $this->assertEquals(2500.00, (float) $invoice1->subtotal);
        $this->assertEquals(3000.00, (float) $invoice1->total);

        $invoice1->update(['status' => 'paid', 'paid_at' => now(), 'amount_paid' => 3000.00, 'amount_due' => 0]);
        $this->ok("Zaliczka opłacona ✔ [paid]");
        $this->logEmail($this->client->primary_contact_email, "Potwierdzenie płatności – {$invoice1->number}", "Otrzymaliśmy płatność £3,000. Projekt startuje!");

        // ── 8. Fazy projektu ─────────────────────────────────────────
        $this->step('[8/10] Realizacja projektu – 5 faz');

        $phaseNames = ['Discovery & Strategy', 'Design', 'Development', 'Testing & QA', 'Launch'];
        foreach ($phaseNames as $i => $name) {
            ProjectPhase::create(['project_id' => $project->id, 'name' => $name, 'order' => $i + 1, 'status' => 'pending']);
        }

        $project->load('phases');
        $this->assertCount(5, $project->phases);

        foreach ($project->phases as $phase) {
            $phase->update(['status' => 'active']);
            $phase->update(['status' => 'completed']);
            $this->logPhase($phase->name, 'completed');
        }

        // ── 9. Faktura końcowa 50% ───────────────────────────────────
        $this->step('[9/10] Faktura końcowa 50%');

        $invoice2 = Invoice::create([
            'number'     => 'INV-E2E-002',
            'client_id'  => $this->client->id,
            'project_id' => $project->id,
            'created_by' => $this->manager->id,
            'status'     => 'sent',
            'currency'   => 'GBP',
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(7)->toDateString(),
            'vat_rate'   => 20,
            'sent_at'    => now(),
        ]);
        InvoiceItem::create(['invoice_id' => $invoice2->id, 'description' => 'Płatność końcowa 50% – Sklep internetowy', 'quantity' => 1, 'unit_price' => 2500.00, 'order' => 1]);
        $invoice2->recalculate();
        $invoice2->refresh();

        $this->logInvoice($invoice2->number, (float)$invoice2->subtotal, (float)$invoice2->vat_amount, (float)$invoice2->total, 'sent');
        $this->logEmail($this->client->primary_contact_email,
            "Faktura {$invoice2->number} – Płatność końcowa (£" . number_format((float)$invoice2->total, 2) . " brutto)",
            "Projekt ukończony! Faktura końcowa do opłacenia do " . now()->addDays(7)->toDateString() . "."
        );

        $invoice2->update(['status' => 'paid', 'paid_at' => now(), 'amount_paid' => 3000.00, 'amount_due' => 0]);
        $this->ok("Płatność końcowa opłacona ✔ [paid]");

        // ── 10. Zamknięcie projektu ───────────────────────────────────
        $this->step('[10/10] Zamknięcie projektu');

        $project->update(['status' => 'completed', 'completed_at' => now()]);
        $project->refresh();

        $this->ok("Projekt [{$project->status}] | completed_at: " . $project->completed_at->format('Y-m-d H:i:s'));
        $this->logEmail($this->client->primary_contact_email, "Projekt gotowy – dostępy i dokumentacja 🎉",
            "Drogi Jan, projekt '{$project->title}' został ukończony i przekazany. W załączeniu dostępy do panelu, instrukcja obsługi i dane hostingu. Dziękujemy za współpracę!");
        $this->logSms($this->client->primary_contact_phone, "Jan, Twój sklep jest gotowy! Sprawdź maila ze szczegółami. Dziękujemy za zaufanie! – NoName Agency");

        // ── Asercje ───────────────────────────────────────────────────
        $this->assertEquals('completed', $project->status);
        $this->assertNotNull($project->completed_at);

        $activities = LeadActivity::where('lead_id', $lead->id)->get();
        $this->assertTrue($activities->where('type', 'created')->isNotEmpty(),         'Brak wpisu: created');
        $this->assertTrue($activities->where('type', 'stage_moved')->count() >= 3,     'Oczekiwano ≥3 stage_moved');
        $this->assertTrue($activities->where('type', 'email_sent')->count() >= 3,      'Oczekiwano ≥3 email_sent');
        $this->assertTrue($activities->where('type', 'sms_sent')->isNotEmpty(),        'Brak wpisu: sms_sent');
        $this->assertTrue($activities->where('type', 'marked_won')->isNotEmpty(),      'Brak wpisu: marked_won');
        $this->assertTrue($activities->where('type', 'project_created')->isNotEmpty(), 'Brak wpisu: project_created');

        $this->assertCount(2, $project->invoices);
        $this->assertEquals('paid', $invoice1->fresh()->status);
        $this->assertEquals('paid', $invoice2->fresh()->status);

        // ── Aktywności w DB ───────────────────────────────────────────
        fwrite(STDOUT, "\n  ┌─── Activity Log (DB) " . str_repeat('─', 38) . "┐\n");
        foreach ($activities->sortBy('id') as $a) {
            fwrite(STDOUT, "  │  [{$a->type}]  {$a->description}\n");
        }
        fwrite(STDOUT, "  │  Total: {$activities->count()} aktywności\n");
        $this->sep();

        // ── Outbox summary ────────────────────────────────────────────
        $this->dumpOutbox();
    }
}
