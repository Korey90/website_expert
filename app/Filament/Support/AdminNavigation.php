<?php

namespace App\Filament\Support;

use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\AgedReceivablesPayablesPage;
use App\Filament\Pages\BalanceSheetPage;
use App\Filament\Pages\BankReconciliationReportPage;
use App\Filament\Pages\CalculatorAdminPage;
use App\Filament\Pages\CalendarPage;
use App\Filament\Pages\ConversionReportPage;
use App\Filament\Pages\ExpenseReportPage;
use App\Filament\Pages\GeneralLedgerPage;
use App\Filament\Pages\HmrcConnectionPage;
use App\Filament\Pages\IntegrationSettingsPage;
use App\Filament\Pages\JobManagerPage;
use App\Filament\Pages\LegalSettingsPage;
use App\Filament\Pages\LogViewerPage;
use App\Filament\Pages\ManagementCashFlowPage;
use App\Filament\Pages\NavbarSettingsPage;
use App\Filament\Pages\PaymentSettingsPage;
use App\Filament\Pages\PipelinePage;
use App\Filament\Pages\ProfitAndLossPage;
use App\Filament\Pages\SitemapPage;
use App\Filament\Pages\TrackingSettingsPage;
use App\Filament\Pages\TrialBalancePage;
use App\Filament\Pages\VatDetailPage;
use App\Filament\Resources\AccountingPeriodResource;
use App\Filament\Resources\ArticleResource;
use App\Filament\Resources\AutomationLogResource;
use App\Filament\Resources\AutomationRuleResource;
use App\Filament\Resources\AutomationTriggerResource;
use App\Filament\Resources\BankAccountResource;
use App\Filament\Resources\BankTransactionResource;
use App\Filament\Resources\BriefingResource;
use App\Filament\Resources\BriefingTemplateResource;
use App\Filament\Resources\BusinessResource;
use App\Filament\Resources\CalendarEventResource;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ContractResource;
use App\Filament\Resources\ContractTemplateResource;
use App\Filament\Resources\CreditNoteResource;
use App\Filament\Resources\DomainOrderResource;
use App\Filament\Resources\DomainPriceListResource;
use App\Filament\Resources\DomainResource;
use App\Filament\Resources\EmailTemplateResource;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\JournalEntryResource;
use App\Filament\Resources\LandingPageResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LedgerAccountResource;
use App\Filament\Resources\NotificationResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\PipelineStageResource;
use App\Filament\Resources\PlanResource;
use App\Filament\Resources\PortfolioProjectResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectTemplateResource;
use App\Filament\Resources\PurchaseCreditNoteResource;
use App\Filament\Resources\QuoteResource;
use App\Filament\Resources\RecurringExpenseTemplateResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SalesOfferResource;
use App\Filament\Resources\SalesOfferTemplateResource;
use App\Filament\Resources\SearchRankingCheckResource;
use App\Filament\Resources\SecurityEventResource;
use App\Filament\Resources\ServiceItemResource;
use App\Filament\Resources\ServicePageResource;
use App\Filament\Resources\SessionResource;
use App\Filament\Resources\SiteSectionResource;
use App\Filament\Resources\SmsTemplateResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\SupplierResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VatAdjustmentResource;
use App\Filament\Resources\VatReturnResource;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

class AdminNavigation
{
    public static function build(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->groups(static::groups());
    }

    /** @return list<NavigationGroup> */
    public static function groups(): array
    {
        return array_values(array_filter([
            static::group('admin.navigation.groups.start', 'heroicon-o-home', [
                static::item(AdminDashboard::class, 'admin.navigation.items.dashboard'),
                static::item(NotificationResource::class, 'admin.navigation.items.notifications'),
            ]),
            static::group('admin.navigation.groups.sales', 'heroicon-o-funnel', [
                static::item(PipelinePage::class, 'admin.navigation.items.pipeline'),
                static::item(LeadResource::class, 'admin.navigation.items.leads'),
                static::item(ClientResource::class, 'admin.navigation.items.clients'),
                static::item(BriefingResource::class, 'admin.navigation.items.briefings'),
                static::item(SalesOfferResource::class, 'admin.navigation.items.offers'),
                static::parent('admin.navigation.items.sales_configuration', 'heroicon-o-adjustments-horizontal', [
                    static::item(PipelineStageResource::class, 'admin.navigation.items.pipeline_stages'),
                    static::item(BriefingTemplateResource::class, 'admin.navigation.items.briefing_templates'),
                    static::item(SalesOfferTemplateResource::class, 'admin.navigation.items.offer_templates'),
                    static::item(ConversionReportPage::class, 'admin.navigation.items.conversion_report'),
                ]),
            ]),
            static::group('admin.navigation.groups.delivery', 'heroicon-o-briefcase', [
                static::item(ProjectResource::class, 'admin.navigation.items.projects'),
                static::parent('admin.navigation.items.calendar', 'heroicon-o-calendar-days', [
                    static::item(CalendarPage::class, 'admin.navigation.items.calendar'),
                    static::item(CalendarEventResource::class, 'admin.navigation.items.calendar_events'),
                ]),
                static::parent('admin.navigation.items.domains', 'heroicon-o-globe-alt', [
                    static::item(DomainResource::class, 'admin.navigation.items.domains'),
                    static::item(DomainOrderResource::class, 'admin.navigation.items.domain_orders'),
                    static::item(DomainPriceListResource::class, 'admin.navigation.items.domain_pricing'),
                ]),
                static::parent('admin.navigation.items.delivery_configuration', 'heroicon-o-rectangle-stack', [
                    static::item(ProjectTemplateResource::class, 'admin.navigation.items.project_templates'),
                ]),
            ]),
            static::group('admin.navigation.groups.finance', 'heroicon-o-banknotes', [
                static::parent('admin.navigation.items.finance_sales', 'heroicon-o-document-text', [
                    static::item(InvoiceResource::class, 'admin.navigation.items.invoices'),
                    static::item(QuoteResource::class, 'admin.navigation.items.quotes'),
                    static::item(ContractResource::class, 'admin.navigation.items.contracts'),
                    static::item(CreditNoteResource::class, 'admin.navigation.items.credit_notes'),
                    static::item(ContractTemplateResource::class, 'admin.navigation.items.contract_templates'),
                    static::item(PaymentResource::class, 'admin.navigation.items.payments'),
                ]),
                static::parent('admin.navigation.items.purchases', 'heroicon-o-receipt-percent', [
                    static::item(SupplierResource::class, 'admin.navigation.items.suppliers'),
                    static::item(ExpenseResource::class, 'admin.navigation.items.expenses'),
                    static::item(PurchaseCreditNoteResource::class, 'admin.navigation.items.purchase_credits'),
                    static::item(RecurringExpenseTemplateResource::class, 'admin.navigation.items.recurring_expenses'),
                ]),
                static::parent('admin.navigation.items.banking', 'heroicon-o-building-library', [
                    static::item(BankAccountResource::class, 'admin.navigation.items.bank_accounts'),
                    static::item(BankTransactionResource::class, 'admin.navigation.items.bank_reconciliation'),
                ]),
                static::parent('admin.navigation.items.accounting_vat', 'heroicon-o-book-open', [
                    static::item(LedgerAccountResource::class, 'admin.navigation.items.chart_of_accounts'),
                    static::item(AccountingPeriodResource::class, 'admin.navigation.items.accounting_periods'),
                    static::item(VatAdjustmentResource::class, 'admin.navigation.items.vat_adjustments'),
                    static::item(JournalEntryResource::class, 'admin.navigation.items.journals'),
                    static::item(VatReturnResource::class, 'admin.navigation.items.vat_returns'),
                    static::item(GeneralLedgerPage::class, 'admin.navigation.items.general_ledger'),
                    static::item(HmrcConnectionPage::class, 'admin.navigation.items.hmrc'),
                ]),
                static::parent('admin.navigation.items.reports', 'heroicon-o-chart-bar', [
                    static::item(TrialBalancePage::class, 'admin.navigation.items.trial_balance'),
                    static::item(ExpenseReportPage::class, 'admin.navigation.items.expense_report'),
                    static::item(ProfitAndLossPage::class, 'admin.navigation.items.profit_loss'),
                    static::item(BalanceSheetPage::class, 'admin.navigation.items.balance_sheet'),
                    static::item(BankReconciliationReportPage::class, 'admin.navigation.items.bank_reconciliation_report'),
                    static::item(ManagementCashFlowPage::class, 'admin.navigation.items.cash_flow'),
                    static::item(AgedReceivablesPayablesPage::class, 'admin.navigation.items.aged_receivables_payables'),
                    static::item(VatDetailPage::class, 'admin.navigation.items.vat_detail'),
                ]),
            ]),
            static::group('admin.navigation.groups.marketing', 'heroicon-o-megaphone', [
                static::item(NavbarSettingsPage::class, 'admin.navigation.items.navigation_menu'),
                static::item(LandingPageResource::class, 'admin.navigation.items.landing_pages'),
                static::parent('admin.navigation.items.pages_sections', 'heroicon-o-document-duplicate', [
                    static::item(PageResource::class, 'admin.navigation.items.cms_pages'),
                    static::item(SiteSectionResource::class, 'admin.navigation.items.frontend_sections'),
                    static::item(ArticleResource::class, 'admin.navigation.items.blog_articles'),
                ]),
                static::parent('admin.navigation.items.communication', 'heroicon-o-envelope', [
                    static::item(EmailTemplateResource::class, 'admin.navigation.items.email_templates'),
                    static::item(SmsTemplateResource::class, 'admin.navigation.items.sms_templates'),
                ]),
                static::parent('admin.navigation.items.services_portfolio', 'heroicon-o-photo', [
                    static::item(ServiceItemResource::class, 'admin.navigation.items.services'),
                    static::item(ServicePageResource::class, 'admin.navigation.items.service_pages'),
                    static::item(PortfolioProjectResource::class, 'admin.navigation.items.portfolio'),
                ]),
                static::parent('admin.navigation.items.seo', 'heroicon-o-magnifying-glass', [
                    static::item(SearchRankingCheckResource::class, 'admin.navigation.items.rank_checker'),
                    static::item(SitemapPage::class, 'admin.navigation.items.sitemap'),
                ]),
            ]),
            static::group('admin.navigation.groups.automation', 'heroicon-o-bolt', [
                static::item(AutomationRuleResource::class, 'admin.navigation.items.automation_rules'),
                static::item(AutomationTriggerResource::class, 'admin.navigation.items.automation_triggers'),
                static::item(AutomationLogResource::class, 'admin.navigation.items.automation_logs'),
            ]),
            static::group('admin.navigation.groups.administration', 'heroicon-o-cog-6-tooth', [
                static::parent('admin.navigation.items.users_access', 'heroicon-o-users', [
                    static::item(UserResource::class, 'admin.navigation.items.users'),
                    static::item(RoleResource::class, 'admin.navigation.items.roles'),
                    static::item(PermissionResource::class, 'admin.navigation.items.permissions'),
                    static::item(SessionResource::class, 'admin.navigation.items.sessions'),
                ]),
                static::parent('admin.navigation.items.businesses_subscriptions', 'heroicon-o-building-storefront', [
                    static::item(BusinessResource::class, 'admin.navigation.items.businesses'),
                    static::item(PlanResource::class, 'admin.navigation.items.plans'),
                    static::item(SubscriptionResource::class, 'admin.navigation.items.subscriptions'),
                ]),
                static::parent('admin.navigation.items.integrations', 'heroicon-o-puzzle-piece', [
                    static::item(IntegrationSettingsPage::class, 'admin.navigation.items.integrations'),
                    static::item(PaymentSettingsPage::class, 'admin.navigation.items.payment_settings'),
                    static::item(TrackingSettingsPage::class, 'admin.navigation.items.tracking'),
                ]),
                static::parent('admin.navigation.items.company_settings', 'heroicon-o-building-office', [
                    static::item(LegalSettingsPage::class, 'admin.navigation.items.legal_company'),
                    static::item(CalculatorAdminPage::class, 'admin.navigation.items.calculator'),
                ]),
                static::item(SecurityEventResource::class, 'admin.navigation.items.security_events'),
                static::parent('admin.navigation.items.system_tools', 'heroicon-o-wrench-screwdriver', [
                    static::item(LogViewerPage::class, 'admin.navigation.items.log_viewer'),
                    static::item(JobManagerPage::class, 'admin.navigation.items.job_manager'),
                ]),
            ]),
        ]));
    }

    /** @return list<array{label: string, url: string, section: string}> */
    public static function searchableDestinations(): array
    {
        $destinations = [];

        foreach (static::groups() as $group) {
            foreach ($group->getItems() as $item) {
                $children = $item->getChildItems();

                if (filled($children)) {
                    foreach ($children as $child) {
                        if (filled($child->getUrl())) {
                            $destinations[] = [
                                'label' => $child->getLabel(),
                                'url' => $child->getUrl(),
                                'section' => $group->getLabel() ?? '',
                            ];
                        }
                    }

                    continue;
                }

                if (filled($item->getUrl())) {
                    $destinations[] = [
                        'label' => $item->getLabel(),
                        'url' => $item->getUrl(),
                        'section' => $group->getLabel() ?? '',
                    ];
                }
            }
        }

        return collect($destinations)
            ->unique('url')
            ->values()
            ->all();
    }

    /** @param array<NavigationItem|null> $items */
    private static function group(string $labelKey, string $icon, array $items): ?NavigationGroup
    {
        $items = array_values(array_filter($items));

        if ($items === []) {
            return null;
        }

        return NavigationGroup::make(__($labelKey))
            ->icon($icon)
            ->items($items)
            ->collapsible();
    }

    /** @param array<NavigationItem|null> $children */
    private static function parent(string $labelKey, string $icon, array $children): ?NavigationItem
    {
        $children = array_values(array_filter($children));

        if ($children === []) {
            return null;
        }

        return NavigationItem::make(__($labelKey))
            ->key($labelKey)
            ->icon($icon)
            ->url($children[0]->getUrl())
            ->childItems($children)
            ->isActiveWhen(fn (): bool => collect($children)->contains(
                fn (NavigationItem $item): bool => $item->isActive() || $item->isChildItemsActive()
            ));
    }

    /** @param class-string $class */
    private static function item(string $class, string $labelKey): ?NavigationItem
    {
        if (! $class::canAccess()) {
            return null;
        }

        $item = $class::getNavigationItems()[0] ?? null;

        if (! $item) {
            return null;
        }

        return $item
            ->label(__($labelKey))
            ->group(null)
            ->parentItem(null);
    }
}
