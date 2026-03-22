<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Mail\ClientEmailMail;
use App\Mail\QuoteSentMail;
use App\Models\CalculatorPricing;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadChecklistItem;
use App\Models\LeadNote;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class ViewLead extends ViewRecord
{
    use WithFileUploads;
    protected static string $resource = LeadResource::class;

    protected string $view = 'filament.pages.view-lead';

    // ── Email modal ───────────────────────────────────────────────────────────
    public bool            $showEmailModal  = false;
    public string|int|null $emailTemplateId = null;
    public string          $emailSubject    = '';
    public string          $emailBody       = '';
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array           $emailAttachments = [];

    // ── Notes ─────────────────────────────────────────────────────────────
    public string $newNoteText  = '';
    public ?int   $editNoteId   = null;
    public string $editNoteText = '';

    // ── Checklist item modal ─────────────────────────────────────────────
    public bool   $showChecklistModal      = false;
    public ?int   $checklistModalIndex     = null;
    public string $checklistModalCondition = '';
    public string $checklistModalLabel     = '';
    public string $modalBudgetMin          = '';
    public string $modalBudgetMax          = '';
    public ?int   $modalAssignedTo         = null;
    public string $modalExpectedClose      = '';
    public string $modalNoteText           = '';
    public string $modalPhone              = '';
    public string $modalEmail              = '';

    // ── SMS modal ─────────────────────────────────────────────────────────
    public bool            $showSmsModal   = false;
    public string|int|null $smsTemplateId  = null;
    public string          $smsMessage     = '';

    // ── Proposal builder ─────────────────────────────────────────────────
    public bool   $showProposalModal  = false;
    public ?int   $proposalQuoteId    = null;
    public string $proposalCurrency   = 'GBP';
    public string $proposalVatRate    = '20';
    public string $proposalDiscount   = '0';
    public string $proposalValidUntil = '';
    public string $proposalNotes      = '';
    public string $proposalTerms      = '';
    public array  $proposalItems      = [];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getViewData(): array
    {
        $this->record->loadMissing(['client', 'stage', 'assignedTo', 'contact', 'project']);

        $activities = LeadActivity::where('lead_id', $this->record->id)
            ->with('user')
            ->latest()
            ->get();

        $leadNotes = LeadNote::where('lead_id', $this->record->id)
            ->with('user')
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        $allStages      = PipelineStage::orderBy('order')->get();
        $hasProject     = $this->record->project !== null;
        $emailTemplates = EmailTemplate::where('is_active', true)->orderBy('name')->get();
        $smsTemplates   = SmsTemplate::where('is_active', true)->orderBy('name')->get();

        $stageChecklist = $this->record->stage?->checklist ?? [];
        $completedItems = LeadChecklistItem::where('lead_id', $this->record->id)
            ->where('pipeline_stage_id', $this->record->pipeline_stage_id)
            ->get()
            ->keyBy('item_index');

        // Evaluate auto-conditions for each checklist item
        $autoSatisfied = [];
        foreach ($stageChecklist as $i => $item) {
            $condition = $item['condition'] ?? null;
            $autoSatisfied[$i] = $condition ? $this->evaluateChecklistCondition($condition) : false;
        }

        $users = User::orderBy('name')->get(['id', 'name']);

        $existingQuote = Quote::where('lead_id', $this->record->id)
            ->latest()
            ->first();

        return compact('activities', 'leadNotes', 'allStages', 'hasProject', 'emailTemplates', 'smsTemplates', 'stageChecklist', 'completedItems', 'autoSatisfied', 'users', 'existingQuote');
    }

    // ── Email ─────────────────────────────────────────────────────────────

    public function openEmailModal(): void
    {
        $this->emailTemplateId  = null;
        $this->emailSubject     = '';
        $this->emailBody        = '';
        $this->emailAttachments = [];
        $this->showEmailModal   = true;
    }

    public function updatedEmailTemplateId(string|int|null $value): void
    {
        $id = $value ? (int) $value : null;

        if (! $id) {
            $this->emailSubject = '';
            $this->emailBody    = '';
            return;
        }

        $tpl = EmailTemplate::find($id);
        if (! $tpl) {
            return;
        }

        $resolved = $tpl->getForLocale(app()->getLocale());
        $subject  = $resolved['subject']   ?? '';
        $body     = $resolved['body_html'] ?? '';

        $lead = Lead::with('client')->find($this->record->id);
        if ($lead) {
            $vars = [
                '{{client_name}}'  => $lead->client?->primary_contact_name ?? '',
                '{{company_name}}' => $lead->client?->company_name         ?? '',
                '{{lead_title}}'   => $lead->title,
            ];
            $subject = str_replace(array_keys($vars), array_values($vars), $subject);
            $body    = str_replace(array_keys($vars), array_values($vars), $body);
        }

        $this->emailSubject = $subject;
        $this->emailBody    = $body;
    }

    public function sendEmail(): void
    {
        $this->validate([
            'emailSubject'    => ['required', 'string', 'max:255'],
            'emailBody'       => ['required', 'string'],
            'emailAttachments'   => ['array', 'max:5'],
            'emailAttachments.*' => ['file', 'max:10240'],
        ]);

        $lead    = Lead::with('client')->findOrFail($this->record->id);
        $toEmail = $lead->client?->primary_contact_email;

        if (! $toEmail) {
            Notification::make()->title('No email address on file for this client')->danger()->send();
            return;
        }

        // Store uploaded files to a persistent temp location for the queued job
        $attachmentPaths = [];
        foreach ($this->emailAttachments as $file) {
            $stored = $file->storeAs(
                'email-attachments/tmp',
                uniqid('att_', true) . '_' . $file->getClientOriginalName(),
                'local',
            );
            $attachmentPaths[] = Storage::disk('local')->path($stored);
        }

        Mail::to($toEmail)->queue(new ClientEmailMail($this->emailSubject, $this->emailBody, $attachmentPaths));

        LeadActivity::log($this->record->id, 'email_sent', "Email sent to {$toEmail}: \"{$this->emailSubject}\"", [
            'to'          => $toEmail,
            'subject'     => $this->emailSubject,
            'attachments' => count($attachmentPaths),
        ]);

        $this->showEmailModal   = false;
        $this->emailAttachments = [];
        Notification::make()->title("Email queued for {$toEmail}")->success()->send();
    }

    public function removeEmailAttachment(int $index): void
    {
        $attachments = $this->emailAttachments;
        array_splice($attachments, $index, 1);
        $this->emailAttachments = $attachments;
    }

    // ── Notes CRUD ────────────────────────────────────────────────────────

    // ── SMS ───────────────────────────────────────────────────────────────

    public function openSmsModal(): void
    {
        $this->smsTemplateId = null;
        $this->smsMessage    = '';
        $this->showSmsModal  = true;
    }

    public function updatedSmsTemplateId(string|int|null $value): void
    {
        $id = $value ? (int) $value : null;
        if (! $id) {
            $this->smsMessage = '';
            return;
        }

        $tpl = SmsTemplate::find($id);
        if (! $tpl) {
            return;
        }

        $lead = Lead::with(['client', 'stage', 'assignedTo'])->find($this->record->id);
        $vars = [
            'client_name'   => $lead?->client?->primary_contact_name ?? '',
            'company_name'  => $lead?->client?->company_name         ?? '',
            'lead_title'    => $lead?->title                         ?? '',
            'stage_name'    => $lead?->stage?->name                  ?? '',
            'assigned_name' => $lead?->assignedTo?->name             ?? '',
            'project_name'  => $lead?->project?->name                ?? '',
            'today'         => now()->format('d M Y'),
        ];

        $this->smsMessage = $tpl->render($vars);
    }

    public function sendSmsFromLead(): void
    {
        $this->validate([
            'smsMessage' => ['required', 'string', 'max:1600'],
        ]);

        $lead  = Lead::with('client')->findOrFail($this->record->id);
        $phone = $lead->client?->primary_contact_phone;

        if (! $phone) {
            Notification::make()->title('No phone number on file for this client')->danger()->send();
            return;
        }

        $sent = app(SmsService::class)->send($phone, $this->smsMessage);

        if (! $sent) {
            Notification::make()->title('SMS failed — check logs')->danger()->send();
            return;
        }

        LeadActivity::log($this->record->id, 'sms_sent', 'SMS sent to ' . $phone . ': "' . mb_substr($this->smsMessage, 0, 80) . (mb_strlen($this->smsMessage) > 80 ? '…' : '') . '"');

        $this->showSmsModal  = false;
        $this->smsTemplateId = null;
        $this->smsMessage    = '';

        Notification::make()->title('SMS sent to ' . $phone)->success()->send();
    }

    public function addNote(): void
    {
        $this->validate(['newNoteText' => ['required', 'string', 'max:10000']]);

        LeadNote::create([
            'lead_id' => $this->record->id,
            'user_id' => Auth::id(),
            'content' => $this->newNoteText,
        ]);

        LeadActivity::log($this->record->id, 'note_updated', 'Note added', [
            'preview' => mb_substr($this->newNoteText, 0, 120),
        ]);

        $this->newNoteText = '';
        Notification::make()->title('Note added')->success()->send();
    }

    public function startEditNote(int $noteId): void
    {
        $note = LeadNote::findOrFail($noteId);
        $this->editNoteId   = $noteId;
        $this->editNoteText = $note->content;
    }

    public function saveEditNote(): void
    {
        $this->validate(['editNoteText' => ['required', 'string', 'max:10000']]);

        LeadNote::findOrFail($this->editNoteId)->update(['content' => $this->editNoteText]);

        $this->editNoteId   = null;
        $this->editNoteText = '';
        Notification::make()->title('Note updated')->success()->send();
    }

    public function cancelEditNote(): void
    {
        $this->editNoteId   = null;
        $this->editNoteText = '';
    }

    public function deleteNote(int $noteId): void
    {
        LeadNote::findOrFail($noteId)->delete();
        Notification::make()->title('Note deleted')->success()->send();
    }

    public function togglePinNote(int $noteId): void
    {
        $note = LeadNote::findOrFail($noteId);
        $note->update(['is_pinned' => ! $note->is_pinned]);
    }

    // ── Quick Actions ─────────────────────────────────────────────────────

    protected function evaluateChecklistCondition(string $condition): bool
    {
        $lead = $this->record;

        return match ($condition) {
            'has_assignee'        => $lead->assigned_to !== null,
            'has_value'           => $lead->value !== null && (float) $lead->value > 0,
            'has_client'          => $lead->client_id !== null,
            'has_contact'         => $lead->contact_id !== null,
            'has_expected_close'  => $lead->expected_close_date !== null,
            'has_phone'           => ! empty($lead->client?->primary_contact_phone),
            'has_email'           => ! empty($lead->client?->primary_contact_email),
            'email_sent'          => $lead->activities()->where('type', 'email_sent')->exists(),
            'has_project'         => $lead->project()->exists(),
            'has_notes'           => $lead->notes()->exists(),
            'has_calculator_data' => ! empty($lead->calculator_data),
            default               => false,
        };
    }

    public function toggleChecklistItem(int $index): void
    {
        // Prevent toggling auto-satisfied items
        $checklist = $this->record->stage?->checklist ?? [];
        $condition = $checklist[$index]['condition'] ?? null;
        if ($condition && $this->evaluateChecklistCondition($condition)) {
            return;
        }
        $existing = LeadChecklistItem::where([
            'lead_id'           => $this->record->id,
            'pipeline_stage_id' => $this->record->pipeline_stage_id,
            'item_index'        => $index,
        ])->first();

        if ($existing) {
            $existing->delete();
        } else {
            LeadChecklistItem::create([
                'lead_id'           => $this->record->id,
                'pipeline_stage_id' => $this->record->pipeline_stage_id,
                'item_index'        => $index,
                'completed_by'      => Auth::id(),
                'completed_at'      => now(),
            ]);
        }
    }

    // ── Checklist item modal methods ──────────────────────────────────────

    public function openChecklistItemModal(int $index): void
    {
        $lead      = $this->record;
        $checklist = $lead->stage?->checklist ?? [];
        $item      = $checklist[$index] ?? null;
        if (! $item) {
            return;
        }

        $this->checklistModalIndex     = $index;
        $this->checklistModalCondition = $item['condition'] ?? '';
        $this->checklistModalLabel     = $item['label'] ?? '';

        // Pre-fill current values
        switch ($this->checklistModalCondition) {
            case 'has_value':
                $this->modalBudgetMin = $lead->budget_min !== null ? (string) $lead->budget_min : '';
                $this->modalBudgetMax = $lead->budget_max !== null ? (string) $lead->budget_max : '';
                break;
            case 'has_assignee':
                $this->modalAssignedTo = $lead->assigned_to;
                break;
            case 'has_expected_close':
                $this->modalExpectedClose = $lead->expected_close_date?->format('Y-m-d') ?? '';
                break;
            case 'has_notes':
                $this->modalNoteText = '';
                break;
            case 'has_phone':
                $this->modalPhone = $lead->client?->primary_contact_phone ?? '';
                break;
            case 'has_email':
                $this->modalEmail = $lead->client?->primary_contact_email ?? '';
                break;
        }

        $this->showChecklistModal = true;
    }

    public function saveChecklistModal(): void
    {
        $lead = $this->record;

        switch ($this->checklistModalCondition) {
            case 'has_value':
                $this->validate([
                    'modalBudgetMin' => ['nullable', 'numeric', 'min:0'],
                    'modalBudgetMax' => ['nullable', 'numeric', 'min:0'],
                ]);
                $lead->update([
                    'budget_min' => $this->modalBudgetMin !== '' ? $this->modalBudgetMin : null,
                    'budget_max' => $this->modalBudgetMax !== '' ? $this->modalBudgetMax : null,
                    'value'      => $this->modalBudgetMax !== '' ? $this->modalBudgetMax
                                    : ($this->modalBudgetMin !== '' ? $this->modalBudgetMin : $lead->value),
                ]);
                LeadActivity::log($lead->id, 'updated', 'Budget range updated', [
                    'min' => $this->modalBudgetMin,
                    'max' => $this->modalBudgetMax,
                ]);
                break;

            case 'has_assignee':
                $this->validate(['modalAssignedTo' => ['nullable', 'integer', 'exists:users,id']]);
                $lead->update(['assigned_to' => $this->modalAssignedTo]);
                $assignedName = $this->modalAssignedTo
                    ? User::find($this->modalAssignedTo)?->name
                    : 'nobody';
                LeadActivity::log($lead->id, 'assigned', "Assigned to {$assignedName}");
                break;

            case 'has_expected_close':
                $this->validate(['modalExpectedClose' => ['required', 'date']]);
                $lead->update(['expected_close_date' => $this->modalExpectedClose]);
                LeadActivity::log($lead->id, 'updated', 'Expected close date set', [
                    'date' => $this->modalExpectedClose,
                ]);
                break;

            case 'has_notes':
                $this->validate(['modalNoteText' => ['required', 'string', 'max:10000']]);
                LeadNote::create([
                    'lead_id' => $lead->id,
                    'user_id' => Auth::id(),
                    'content' => $this->modalNoteText,
                ]);
                LeadActivity::log($lead->id, 'note_updated', 'Note added via checklist');
                $this->modalNoteText = '';
                break;

            case 'has_phone':
                $this->validate(['modalPhone' => ['required', 'string', 'max:50']]);
                $lead->client?->update(['primary_contact_phone' => $this->modalPhone]);
                LeadActivity::log($lead->id, 'updated', 'Client phone updated');
                break;

            case 'has_email':
                $this->validate(['modalEmail' => ['required', 'email', 'max:255']]);
                $lead->client?->update(['primary_contact_email' => $this->modalEmail]);
                LeadActivity::log($lead->id, 'updated', 'Client email updated');
                break;

            default:
                $this->showChecklistModal = false;
                return;
        }

        $lead->refresh();
        $this->record->refresh();
        $this->showChecklistModal = false;
        Notification::make()->title('Saved successfully')->success()->send();
    }

    // ── Proposal builder methods ──────────────────────────────────────────

    public function openProposalBuilder(): void
    {
        $existing = Quote::where('lead_id', $this->record->id)
            ->where('status', 'draft')
            ->with('items')
            ->latest()
            ->first();

        if ($existing) {
            $this->proposalQuoteId    = $existing->id;
            $this->proposalCurrency   = $existing->currency;
            $this->proposalVatRate    = (string) $existing->vat_rate;
            $this->proposalDiscount   = (string) $existing->discount_amount;
            $this->proposalValidUntil = $existing->valid_until?->format('Y-m-d') ?? today()->addDays(30)->format('Y-m-d');
            $this->proposalNotes      = $existing->notes ?? '';
            $this->proposalTerms      = $existing->terms ?? 'This quote is valid for 30 days from the date of issue.';
            $this->proposalItems      = $existing->items->map(fn ($item) => [
                'description' => $item->description,
                'details'     => $item->details ?? '',
                'quantity'    => (string) $item->quantity,
                'unit_price'  => (string) $item->unit_price,
            ])->toArray();
        } else {
            $this->proposalQuoteId    = null;
            $this->proposalCurrency   = $this->record->currency ?? 'GBP';
            $this->proposalVatRate    = '20';
            $this->proposalDiscount   = '0';
            $this->proposalValidUntil = today()->addDays(30)->format('Y-m-d');
            $this->proposalNotes      = '';
            $this->proposalTerms      = 'This quote is valid for 30 days from the date of issue.';
            $this->proposalItems      = [
                ['description' => '', 'details' => '', 'quantity' => '1', 'unit_price' => ''],
            ];
        }

        $this->showProposalModal = true;
    }

    public function addProposalItem(): void
    {
        $this->proposalItems[] = ['description' => '', 'details' => '', 'quantity' => '1', 'unit_price' => ''];
    }

    public function removeProposalItem(int $i): void
    {
        $items = $this->proposalItems;
        array_splice($items, $i, 1);
        $this->proposalItems = array_values($items);
    }

    public function getServiceSuggestions(string $search = ''): array
    {
        $q = CalculatorPricing::where('is_active', true);

        if (trim($search) !== '') {
            $q->where(function ($query) use ($search) {
                $query->where('label', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
            });
        }

        return $q->orderBy('sort_order')
            ->orderBy('label')
            ->limit(8)
            ->get(['label', 'description', 'base_cost', 'category'])
            ->toArray();
    }

    public function saveProposalDraft(bool $sendNow = false): void
    {
        $this->validate([
            'proposalItems'               => ['required', 'array', 'min:1'],
            'proposalItems.*.description' => ['required', 'string', 'max:500'],
            'proposalItems.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'proposalItems.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'proposalVatRate'             => ['required', 'numeric', 'min:0', 'max:100'],
            'proposalDiscount'            => ['nullable', 'numeric', 'min:0'],
            'proposalValidUntil'          => ['nullable', 'date'],
        ]);

        $lead      = $this->record;
        $quoteData = [
            'client_id'       => $lead->client_id,
            'lead_id'         => $lead->id,
            'created_by'      => Auth::id(),
            'status'          => 'draft',
            'currency'        => $this->proposalCurrency,
            'vat_rate'        => (float) $this->proposalVatRate,
            'discount_amount' => (float) ($this->proposalDiscount ?: 0),
            'valid_until'     => $this->proposalValidUntil ?: null,
            'notes'           => $this->proposalNotes ?: null,
            'terms'           => $this->proposalTerms ?: null,
        ];

        if ($this->proposalQuoteId) {
            $quote = Quote::findOrFail($this->proposalQuoteId);
            $quote->update($quoteData);
            $quote->items()->delete();
        } else {
            $number = 'QUOT-' . date('Y') . '-' . str_pad(Quote::withTrashed()->count() + 1, 3, '0', STR_PAD_LEFT);
            $quote  = Quote::create(array_merge($quoteData, ['number' => $number]));
            $this->proposalQuoteId = $quote->id;
        }

        foreach ($this->proposalItems as $i => $item) {
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'description' => $item['description'],
                'details'     => $item['details'] ?: null,
                'quantity'    => (float) ($item['quantity'] ?: 1),
                'unit_price'  => (float) ($item['unit_price'] ?: 0),
                'order'       => $i,
            ]);
        }

        $quote->recalculate();

        if ($sendNow) {
            $this->dispatchProposalEmail($quote);
        } else {
            $this->record->refresh();
            $this->showProposalModal = false;
            Notification::make()->title('Draft saved — ' . $quote->number)->success()->send();
        }
    }

    public function sendProposal(): void
    {
        $this->saveProposalDraft(sendNow: true);
    }

    public function sendExistingDraft(): void
    {
        $quote = Quote::where('lead_id', $this->record->id)
            ->where('status', 'draft')
            ->latest()
            ->first();

        if (! $quote) {
            Notification::make()->title('No draft quote found')->danger()->send();
            return;
        }

        if ($quote->items()->count() === 0) {
            Notification::make()->title('Quote has no items — open the builder first')->warning()->send();
            return;
        }

        $quote->recalculate();
        $this->dispatchProposalEmail($quote);
    }

    protected function dispatchProposalEmail(Quote $quote): void
    {
        $toEmail = $this->record->client?->primary_contact_email;

        if (! $toEmail) {
            Notification::make()->title('No email on file for this client')->danger()->send();
            return;
        }

        $quote->update(['status' => 'sent', 'sent_at' => now()]);
        Mail::to($toEmail)->queue(new QuoteSentMail($quote));

        // Auto-advance lead to "Proposal Sent" stage
        $proposalStage = PipelineStage::where('name', 'Proposal Sent')->first();
        if ($proposalStage && $this->record->pipeline_stage_id !== $proposalStage->id) {
            $fromStage = $this->record->stage?->name ?? '?';
            $this->record->update(['pipeline_stage_id' => $proposalStage->id]);
            $this->record->refresh();
            LeadActivity::log($this->record->id, 'stage_moved', "Stage moved: {$fromStage} → Proposal Sent");
        }

        LeadActivity::log($this->record->id, 'email_sent', "Proposal {$quote->number} sent to {$toEmail}", [
            'quote_id'     => $quote->id,
            'quote_number' => $quote->number,
            'to'           => $toEmail,
        ]);

        $this->showProposalModal = false;
        $this->record->refresh();
        Notification::make()->title("Proposal {$quote->number} sent to {$toEmail}")->success()->send();
    }

    public function moveStage(string $direction): void
    {
        $stages = PipelineStage::orderBy('order')->get()->keyBy('id');
        $ids    = $stages->keys()->toArray();
        $idx    = array_search($this->record->pipeline_stage_id, $ids);
        $newIdx = $direction === 'forward' ? $idx + 1 : $idx - 1;

        if ($newIdx < 0 || $newIdx >= count($ids)) {
            Notification::make()->title('Already at boundary stage')->warning()->send();
            return;
        }

        $fromStage = $stages[$this->record->pipeline_stage_id]?->name ?? '?';
        $toStageId = $ids[$newIdx];
        $toStage   = $stages[$toStageId]?->name ?? '?';

        $this->record->update(['pipeline_stage_id' => $toStageId]);
        $this->record->refresh();

        LeadActivity::log($this->record->id, 'stage_moved', "Stage moved: {$fromStage} → {$toStage}", [
            'from' => $fromStage,
            'to'   => $toStage,
        ]);

        Notification::make()->title("Moved to: {$toStage}")->success()->send();
    }

    public function markWon(): void
    {
        $stage = PipelineStage::where('is_won', true)->first();
        if (! $stage) {
            Notification::make()->title('No "Won" stage configured')->warning()->send();
            return;
        }
        $this->record->update(['pipeline_stage_id' => $stage->id, 'won_at' => now()]);
        $this->record->refresh();
        LeadActivity::log($this->record->id, 'marked_won', 'Lead marked as Won');
        Notification::make()->title('Lead marked as Won 🎉')->success()->send();
    }

    public function markLost(): void
    {
        $stage = PipelineStage::where('is_lost', true)->first();
        if (! $stage) {
            Notification::make()->title('No "Lost" stage configured')->warning()->send();
            return;
        }
        $this->record->update(['pipeline_stage_id' => $stage->id, 'lost_at' => now()]);
        $this->record->refresh();
        LeadActivity::log($this->record->id, 'marked_lost', 'Lead marked as Lost');
        Notification::make()->title('Lead marked as Lost')->warning()->send();
    }

    public function convertToProject(): void
    {
        if ($this->record->project()->exists()) {
            Notification::make()->title('Project already exists for this lead')->warning()->send();
            return;
        }

        $project = Project::create([
            'title'        => $this->record->title,
            'client_id'    => $this->record->client_id,
            'lead_id'      => $this->record->id,
            'assigned_to'  => $this->record->assigned_to,
            'service_type' => $this->record->calculator_data['project_type'] ?? null,
            'status'       => 'draft',
            'budget'       => $this->record->value,
            'currency'     => $this->record->currency ?? 'GBP',
        ]);

        LeadActivity::log($this->record->id, 'project_created', 'Converted to project', [
            'project_id' => $project->id,
        ]);

        $this->record->refresh();
        Notification::make()->title('Project created successfully')->success()->send();
    }

    public function assignToSelf(): void
    {
        $user = Auth::user();
        $this->record->update(['assigned_to' => $user->id]);
        $this->record->refresh();
        LeadActivity::log($this->record->id, 'assigned', "Assigned to {$user->name}", [
            'user_id'   => $user->id,
            'user_name' => $user->name,
        ]);
        Notification::make()->title('Lead assigned to you')->success()->send();
    }
}
