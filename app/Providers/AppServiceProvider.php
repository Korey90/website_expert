<?php

namespace App\Providers;

use App\Contracts\Finance\FinancialAttachmentScanner;
use App\Events\LeadAssigned;
use App\Events\LeadCaptured;
use App\Listeners\AutomationEventListener;
use App\Listeners\ClientActivityListener;
use App\Listeners\NotifyLeadOwnerListener;
use App\Livewire\CustomDatabaseNotifications;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\Briefing;
use App\Models\BriefingTemplate;
use App\Models\Expense;
use App\Models\FinancialAttachment;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\PurchaseCreditNote;
use App\Models\RecurringExpenseTemplate;
use App\Models\SalesOffer;
use App\Models\SalesOfferTemplate;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditProfile;
use App\Models\UniversalCreditReportItem;
use App\Models\VatAdjustment;
use App\Models\VatReturn;
use App\Notifications\LeadAssignedNotification;
use App\Observers\PaymentObserver;
use App\Policies\AccountingPeriodPolicy;
use App\Policies\BankAccountPolicy;
use App\Policies\BriefingPolicy;
use App\Policies\BriefingTemplatePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\FinancialAttachmentPolicy;
use App\Policies\FixedAssetCategoryPolicy;
use App\Policies\FixedAssetPolicy;
use App\Policies\LandingPagePolicy;
use App\Policies\LeadPolicy;
use App\Policies\LedgerAccountPolicy;
use App\Policies\PurchaseCreditNotePolicy;
use App\Policies\RecurringExpenseTemplatePolicy;
use App\Policies\SalesOfferPolicy;
use App\Policies\SalesOfferTemplatePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UniversalCreditAssessmentPeriodPolicy;
use App\Policies\UniversalCreditManualEntryPolicy;
use App\Policies\UniversalCreditProfilePolicy;
use App\Policies\UniversalCreditReportItemPolicy;
use App\Policies\VatAdjustmentPolicy;
use App\Policies\VatReturnPolicy;
use App\Services\Finance\ClamAvFinancialAttachmentScanner;
use App\Services\Hmrc\Contracts\HmrcClientInterface;
use App\Services\Hmrc\HmrcEnvironmentGuard;
use App\Services\Hmrc\HmrcProductionClient;
use App\Services\Hmrc\HmrcSandboxClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FinancialAttachmentScanner::class, ClamAvFinancialAttachmentScanner::class);

        $this->app->bind(HmrcClientInterface::class, function () {
            return app(HmrcEnvironmentGuard::class)->environment() === 'production'
                ? new HmrcProductionClient
                : new HmrcSandboxClient;
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Override Filament's DatabaseNotifications so X/close only marks as read, not deletes
        Livewire::component('database-notifications', CustomDatabaseNotifications::class);

        Event::subscribe(AutomationEventListener::class);
        Event::subscribe(ClientActivityListener::class);

        // NotifyLeadOwnerListener: queued, idempotent, fires on new LP lead
        Event::listen(LeadCaptured::class, NotifyLeadOwnerListener::class);

        // LeadAssigned: notify the newly assigned user in-app
        Event::listen(LeadAssigned::class, function (LeadAssigned $event) {
            $event->lead->assignedTo?->notify(
                new LeadAssignedNotification($event->lead, $event->lead->assignedTo)
            );
        });

        Gate::policy(LandingPage::class, LandingPagePolicy::class);
        Gate::policy(AccountingPeriod::class, AccountingPeriodPolicy::class);
        Gate::policy(BankAccount::class, BankAccountPolicy::class);
        Gate::policy(LedgerAccount::class, LedgerAccountPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(PurchaseCreditNote::class, PurchaseCreditNotePolicy::class);
        Gate::policy(RecurringExpenseTemplate::class, RecurringExpenseTemplatePolicy::class);
        Gate::policy(Briefing::class, BriefingPolicy::class);
        Gate::policy(BriefingTemplate::class, BriefingTemplatePolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(FinancialAttachment::class, FinancialAttachmentPolicy::class);
        Gate::policy(FixedAssetCategory::class, FixedAssetCategoryPolicy::class);
        Gate::policy(FixedAsset::class, FixedAssetPolicy::class);
        Gate::policy(SalesOffer::class, SalesOfferPolicy::class);
        Gate::policy(SalesOfferTemplate::class, SalesOfferTemplatePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(UniversalCreditProfile::class, UniversalCreditProfilePolicy::class);
        Gate::policy(UniversalCreditAssessmentPeriod::class, UniversalCreditAssessmentPeriodPolicy::class);
        Gate::policy(UniversalCreditManualEntry::class, UniversalCreditManualEntryPolicy::class);
        Gate::policy(UniversalCreditReportItem::class, UniversalCreditReportItemPolicy::class);
        Gate::policy(VatAdjustment::class, VatAdjustmentPolicy::class);
        Gate::policy(VatReturn::class, VatReturnPolicy::class);

        Payment::observe(PaymentObserver::class);

        $this->applyIntegrationSettings();
    }

    /**
     * Override mail/SMS config at runtime from DB settings saved in IntegrationSettingsPage.
     * Falls back silently if DB is not yet available (e.g. during migrations).
     */
    private function applyIntegrationSettings(): void
    {
        try {
            $mailer = Setting::get('mail_mailer');
            if (! $mailer) {
                return; // Nothing saved yet — use .env defaults
            }

            Config::set('mail.default', $mailer);

            $host = Setting::get('mail_host');
            $port = Setting::get('mail_port');
            $encryption = Setting::get('mail_encryption');
            $username = Setting::get('mail_username');
            $password = Setting::get('mail_password');
            $from = Setting::get('mail_from');
            $fromName = Setting::get('mail_from_name');

            if ($host) {
                Config::set('mail.mailers.smtp.host', $host);
            }
            if ($port) {
                Config::set('mail.mailers.smtp.port', (int) $port);
            }
            if ($encryption !== null) {
                Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
            }
            if ($username) {
                Config::set('mail.mailers.smtp.username', $username);
            }
            if ($password) {
                Config::set('mail.mailers.smtp.password', $password);
            }
            if ($from) {
                Config::set('mail.from.address', $from);
            }
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }

            // Apply to the named mailer that matches the chosen driver
            if (in_array($mailer, ['postmark', 'resend', 'mailgun', 'ses'], true)) {
                Config::set("mail.mailers.{$mailer}", array_merge(
                    config("mail.mailers.{$mailer}", []),
                    ['driver' => $mailer],
                ));
            }

            // Openprovider domain registrar settings
            $opProvider = Setting::get('op_provider');
            $opUsername = Setting::get('op_username');
            $opPassword = Setting::get('op_password');
            $opResellerId = Setting::get('op_reseller_id');
            $opSandbox = Setting::get('op_sandbox');

            if ($opProvider) {
                Config::set('services.domain_registrar.provider', $opProvider);
            }
            if ($opUsername) {
                Config::set('services.domain_registrar.openprovider.username', $opUsername);
            }
            if ($opPassword) {
                Config::set('services.domain_registrar.openprovider.password', $opPassword);
            }
            if ($opResellerId) {
                Config::set('services.domain_registrar.openprovider.reseller_id', $opResellerId);
            }
            if ($opSandbox !== null) {
                Config::set('services.domain_registrar.openprovider.sandbox', $opSandbox !== '0');
            }
        } catch (\Throwable) {
            // DB unavailable (e.g. first deploy) — silently fall back to .env
        }
    }
}
