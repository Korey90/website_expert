<?php

namespace App\Filament\Pages;

use App\Mail\ClientEmailMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadNote;
use App\Models\PipelineStage;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PipelinePage extends BasePage
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';
    protected static \UnitEnum|string|null $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Sales Pipeline';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.pipeline';

    // ── Email modal ───────────────────────────────────────────────────────
    public bool $showEmailModal       = false;
    public ?int $emailLeadId          = null;
    public string|int|null $emailTemplateId = null;
    public string $emailSubject       = '';
    public string $emailBody          = '';

    // ── Note modal ────────────────────────────────────────────────────────
    public bool   $showNoteModal   = false;
    public ?int   $noteLeadId      = null;
    public string $noteLeadTitle   = '';
    public string $newNoteText     = '';
    public ?int   $editNoteId      = null;
    public string $editNoteText    = '';

    // ── History modal ─────────────────────────────────────────────────────
    public bool $showHistoryModal    = false;
    public ?int $historyLeadId       = null;
    public string $historyLeadTitle  = '';

    // ─────────────────────────────────────────────────────────────────────

    public function getTitle(): string
    {
        return 'Sales Pipeline';
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.leads.index') => 'Leads',
            'Sales Pipeline',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list_view')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(route('filament.admin.resources.leads.index')),
            Action::make('new_lead')
                ->label('New Lead')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(route('filament.admin.resources.leads.create')),
        ];
    }

    public function getViewData(): array
    {
        $stages = PipelineStage::orderBy('order')->get();

        $leads = Lead::withoutTrashed()
            ->with(['client', 'stage', 'assignedTo'])
            ->get()
            ->groupBy('pipeline_stage_id');

        $totals = Lead::withoutTrashed()
            ->selectRaw('pipeline_stage_id, COUNT(*) as count, SUM(value) as total_value')
            ->groupBy('pipeline_stage_id')
            ->get()
            ->keyBy('pipeline_stage_id')
            ->map(fn ($r) => ['count' => $r->count, 'total' => $r->total_value ?? 0]);

        $emailTemplates = EmailTemplate::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'body_html']);

        $historyActivities = $this->historyLeadId
            ? LeadActivity::where('lead_id', $this->historyLeadId)
                ->with('user')
                ->latest()
                ->get()
            : collect();

        $leadNotes = $this->noteLeadId
            ? LeadNote::where('lead_id', $this->noteLeadId)
                ->with('user')
                ->orderByDesc('is_pinned')
                ->latest()
                ->get()
            : collect();

        return [
            'stages'            => $stages,
            'leads'             => $leads,
            'totals'            => $totals,
            'emailTemplates'    => $emailTemplates,
            'historyActivities' => $historyActivities,
            'leadNotes'         => $leadNotes,
        ];
    }

    // ── Stage movement ────────────────────────────────────────────────────

    public function moveStage(int $leadId, string $direction): void
    {
        $lead   = Lead::findOrFail($leadId);
        $stages = PipelineStage::orderBy('order')->get()->keyBy('id');
        $ids    = $stages->keys()->toArray();
        $idx    = array_search($lead->pipeline_stage_id, $ids);
        $newIdx = $direction === 'forward' ? $idx + 1 : $idx - 1;

        if ($newIdx < 0 || $newIdx >= count($ids)) {
            return;
        }

        $fromStage = $stages[$lead->pipeline_stage_id]?->name ?? '?';
        $toStageId = $ids[$newIdx];
        $toStage   = $stages[$toStageId]?->name ?? '?';

        $lead->update(['pipeline_stage_id' => $toStageId]);

        LeadActivity::log($leadId, 'stage_moved', "Stage changed: {$fromStage} → {$toStage}", [
            'from_stage' => $fromStage,
            'to_stage'   => $toStage,
        ]);

        Notification::make()->title("Moved to: {$toStage}")->success()->send();
    }

    // ── Win / Loss ────────────────────────────────────────────────────────

    public function markWon(int $leadId): void
    {
        $stage = PipelineStage::where('is_won', true)->first();
        if (! $stage) {
            Notification::make()->title('No "Won" stage configured')->warning()->send();
            return;
        }
        Lead::findOrFail($leadId)->update([
            'pipeline_stage_id' => $stage->id,
            'won_at'            => now(),
        ]);

        LeadActivity::log($leadId, 'marked_won', 'Lead marked as Won 🎉');

        Notification::make()->title('Lead marked as Won 🎉')->success()->send();
    }

    public function markLost(int $leadId): void
    {
        $stage = PipelineStage::where('is_lost', true)->first();
        if (! $stage) {
            Notification::make()->title('No "Lost" stage configured')->warning()->send();
            return;
        }
        Lead::findOrFail($leadId)->update([
            'pipeline_stage_id' => $stage->id,
            'lost_at'           => now(),
        ]);

        LeadActivity::log($leadId, 'marked_lost', 'Lead marked as Lost');

        Notification::make()->title('Lead marked as Lost')->warning()->send();
    }

    // ── Convert to Project ────────────────────────────────────────────────

    public function convertToProject(int $leadId): void
    {
        $lead = Lead::with('client')->findOrFail($leadId);

        if ($lead->project()->exists()) {
            Notification::make()->title('Project already exists for this lead')->warning()->send();
            return;
        }

        $project = Project::create([
            'title'        => $lead->title,
            'client_id'    => $lead->client_id,
            'lead_id'      => $lead->id,
            'assigned_to'  => $lead->assigned_to,
            'service_type' => $lead->calculator_data['project_type'] ?? null,
            'status'       => 'draft',
            'budget'       => $lead->value,
            'currency'     => $lead->currency ?? 'GBP',
        ]);

        LeadActivity::log($leadId, 'project_created', "Project created: \"{$project->title}\"", [
            'project_id' => $project->id,
        ]);

        Notification::make()->title('Project created from lead')->success()->send();
    }

    // ── Email modal ───────────────────────────────────────────────────────

    public function openEmailModal(int $leadId): void
    {
        $this->emailLeadId     = $leadId;
        $this->emailTemplateId = null;
        $this->emailSubject    = '';
        $this->emailBody       = '';
        $this->showEmailModal  = true;
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

        if ($this->emailLeadId) {
            $lead = Lead::with('client')->find($this->emailLeadId);
            if ($lead) {
                $vars = [
                    '{{client_name}}'  => $lead->client?->primary_contact_name ?? '',
                    '{{company_name}}' => $lead->client?->company_name         ?? '',
                    '{{lead_title}}'   => $lead->title,
                ];
                $subject = str_replace(array_keys($vars), array_values($vars), $subject);
                $body    = str_replace(array_keys($vars), array_values($vars), $body);
            }
        }

        $this->emailSubject = $subject;
        $this->emailBody    = strip_tags($body);
    }

    public function sendEmail(): void
    {
        $this->validate([
            'emailSubject' => ['required', 'string', 'max:255'],
            'emailBody'    => ['required', 'string'],
        ]);

        $lead    = Lead::with('client')->findOrFail($this->emailLeadId);
        $toEmail = $lead->client?->primary_contact_email;

        if (! $toEmail) {
            Notification::make()->title('No email address on file for this client')->danger()->send();
            return;
        }

        Mail::to($toEmail)->queue(new ClientEmailMail($this->emailSubject, $this->emailBody));

        LeadActivity::log($this->emailLeadId, 'email_sent', "Email sent to {$toEmail}: \"{$this->emailSubject}\"", [
            'to'      => $toEmail,
            'subject' => $this->emailSubject,
        ]);

        $this->showEmailModal = false;
        Notification::make()->title("Email queued for {$toEmail}")->success()->send();
    }

    // ── Note modal ────────────────────────────────────────────────────────

    public function openNoteModal(int $leadId, string $title): void
    {
        $this->noteLeadId    = $leadId;
        $this->noteLeadTitle = $title;
        $this->newNoteText   = '';
        $this->editNoteId    = null;
        $this->editNoteText  = '';
        $this->showNoteModal = true;
    }

    public function addNote(): void
    {
        $this->validate(['newNoteText' => ['required', 'string', 'max:10000']]);

        LeadNote::create([
            'lead_id' => $this->noteLeadId,
            'user_id' => Auth::id(),
            'content' => $this->newNoteText,
        ]);

        LeadActivity::log($this->noteLeadId, 'note_updated', 'Note added', [
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

    // ── Other actions ─────────────────────────────────────────────────────

    public function assignToSelf(int $leadId): void
    {
        $user = Auth::user();
        Lead::findOrFail($leadId)->update(['assigned_to' => $user->id]);

        LeadActivity::log($leadId, 'assigned', "Assigned to {$user->name}", [
            'user_id'   => $user->id,
            'user_name' => $user->name,
        ]);

        Notification::make()->title('Lead assigned to you')->success()->send();
    }

    public function deleteLead(int $leadId): void
    {
        $lead = Lead::findOrFail($leadId);

        LeadActivity::log($leadId, 'deleted', 'Lead deleted (soft)');

        $lead->delete();
        Notification::make()->title('Lead deleted')->success()->send();
    }

    // ── History modal ─────────────────────────────────────────────────────

    public function openHistoryModal(int $leadId, string $title): void
    {
        $this->historyLeadId    = $leadId;
        $this->historyLeadTitle = $title;
        $this->showHistoryModal = true;
    }
}

