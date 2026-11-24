<?php

namespace App\Filament\Support;

class FilamentPermissionRegistry
{
    private const PANEL_ACCESS_PERMISSION = 'access_admin_panel';

    private const RESOURCE_PERMISSIONS = [
        'AccountingPeriodResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance'],
        'AutomationLogResource' => ['view' => 'view_automation_logs', 'delete' => 'delete_automation_logs'],
        'AutomationRuleResource' => ['view' => 'view_automations', 'create' => 'create_automations', 'update' => 'edit_automations', 'delete' => 'delete_automations'],
        'AutomationTriggerResource' => ['view' => 'view_automation_triggers', 'create' => 'create_automation_triggers', 'update' => 'edit_automation_triggers', 'delete' => 'delete_automation_triggers'],
        'BankAccountResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance'],
        'BankTransactionResource' => ['view' => 'view_finance'],
        'ArticleResource' => ['view' => 'view_articles', 'create' => 'create_articles', 'update' => 'edit_articles', 'delete' => 'delete_articles'],
        'BriefingResource' => ['view' => 'view_briefings', 'create' => 'create_briefings', 'update' => 'edit_briefings', 'delete' => 'delete_briefings'],
        'BriefingTemplateResource' => ['view' => 'view_briefing_templates', 'create' => 'create_briefing_templates', 'update' => 'edit_briefing_templates', 'delete' => 'delete_briefing_templates'],
        'BusinessResource' => ['view' => 'view_businesses', 'update' => 'edit_businesses'],
        'CalculatorPricingResource' => ['view' => 'manage_calculator', 'create' => 'manage_calculator', 'update' => 'manage_calculator', 'delete' => 'manage_calculator'],
        'CalculatorStepsResource' => ['view' => 'manage_calculator', 'create' => 'manage_calculator', 'update' => 'manage_calculator', 'delete' => 'manage_calculator'],
        'CalculatorStringsResource' => ['view' => 'manage_calculator', 'create' => 'manage_calculator', 'update' => 'manage_calculator', 'delete' => 'manage_calculator'],
        'CalendarEventResource' => ['view' => 'view_calendar', 'create' => 'manage_calendar', 'update' => 'manage_calendar', 'delete' => 'manage_calendar'],
        'ClientResource' => ['view' => 'view_clients', 'create' => 'create_clients', 'update' => 'edit_clients', 'delete' => 'delete_clients'],
        'ContractResource' => ['view' => 'view_contracts', 'create' => 'create_contracts', 'update' => 'edit_contracts', 'delete' => 'delete_contracts'],
        'ContractTemplateResource' => ['view' => 'view_contract_templates', 'create' => 'create_contract_templates', 'update' => 'edit_contract_templates', 'delete' => 'delete_contract_templates'],
        'CreditNoteResource' => ['view' => 'view_invoices', 'update' => 'edit_invoices'],
        'DomainOrderResource' => ['view' => 'view_domains'],
        'DomainPriceListResource' => ['view' => 'manage_domain_pricing', 'create' => 'manage_domain_pricing', 'update' => 'manage_domain_pricing', 'delete' => 'manage_domain_pricing'],
        'DomainResource' => ['view' => 'view_domains'],
        'EmailTemplateResource' => ['view' => 'view_email_templates', 'create' => 'create_email_templates', 'update' => 'edit_email_templates', 'delete' => 'delete_email_templates'],
        'ExpenseResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance', 'delete' => 'manage_finance'],
        'FixedAssetCategoryResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance', 'delete' => 'manage_finance'],
        'FixedAssetResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance', 'delete' => 'manage_finance'],
        'InvoiceResource' => ['view' => 'view_invoices', 'create' => 'create_invoices', 'update' => 'edit_invoices', 'delete' => 'delete_invoices'],
        'JournalEntryResource' => ['view' => 'view_finance'],
        'LandingPageResource' => ['view' => 'view_landing_pages', 'create' => 'manage_landing_pages', 'update' => 'manage_landing_pages', 'delete' => 'manage_landing_pages'],
        'LedgerAccountResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance', 'delete' => 'manage_finance'],
        'LeadResource' => ['view' => 'view_leads', 'create' => 'create_leads', 'update' => 'edit_leads', 'delete' => 'delete_leads'],
        'NavItemResource' => ['view' => 'manage_settings', 'create' => 'manage_settings', 'update' => 'manage_settings', 'delete' => 'manage_settings'],
        'NotificationResource' => ['view' => 'view_notifications', 'create' => 'create_notifications', 'delete' => 'delete_notifications'],
        'PageResource' => ['view' => 'view_pages', 'create' => 'create_pages', 'update' => 'edit_pages', 'delete' => 'delete_pages'],
        'PaymentResource' => ['view' => 'view_payments', 'create' => 'create_payments', 'update' => 'edit_payments', 'delete' => 'delete_payments'],
        'PurchaseCreditNoteResource' => ['view' => 'view_finance'],
        'RecurringExpenseTemplateResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance', 'delete' => 'manage_finance'],
        'PermissionResource' => ['view' => 'manage_permissions', 'create' => 'manage_permissions', 'update' => 'manage_permissions', 'delete' => 'manage_permissions'],
        'PipelineStageResource' => ['view' => 'manage_pipeline', 'create' => 'manage_pipeline', 'update' => 'manage_pipeline', 'delete' => 'manage_pipeline'],
        'PlanResource' => ['view' => 'view_plans', 'create' => 'create_plans', 'update' => 'edit_plans', 'delete' => 'delete_plans'],
        'PortfolioProjectResource' => ['view' => 'view_portfolio_projects', 'create' => 'create_portfolio_projects', 'update' => 'edit_portfolio_projects', 'delete' => 'delete_portfolio_projects'],
        'ProjectResource' => ['view' => 'view_projects', 'create' => 'create_projects', 'update' => 'edit_projects', 'delete' => 'delete_projects'],
        'ProjectTemplateResource' => ['view' => 'manage_project_templates', 'create' => 'manage_project_templates', 'update' => 'manage_project_templates', 'delete' => 'manage_project_templates'],
        'QuoteResource' => ['view' => 'view_quotes', 'create' => 'create_quotes', 'update' => 'edit_quotes', 'delete' => 'delete_quotes'],
        'RoleResource' => ['view' => 'manage_roles', 'create' => 'manage_roles', 'update' => 'manage_roles', 'delete' => 'manage_roles'],
        'SalesOfferResource' => ['view' => 'view_sales_offers', 'create' => 'create_sales_offers', 'update' => 'edit_sales_offers', 'delete' => 'delete_sales_offers'],
        'SalesOfferTemplateResource' => ['view' => 'view_sales_offer_templates', 'create' => 'create_sales_offer_templates', 'update' => 'edit_sales_offer_templates', 'delete' => 'delete_sales_offer_templates'],
        'SearchRankingCheckResource' => ['view' => 'view_reports'],
        'SecurityEventResource' => ['view' => 'view_security_events', 'update' => 'manage_security_events'],
        'ServiceItemResource' => ['view' => 'view_services', 'create' => 'create_services', 'update' => 'edit_services', 'delete' => 'delete_services'],
        'ServicePageResource' => ['view' => 'view_pages', 'create' => 'create_pages', 'update' => 'edit_pages', 'delete' => 'delete_pages'],
        'SessionResource' => ['view' => 'view_sessions', 'delete' => 'revoke_sessions'],
        'SiteSectionResource' => ['view' => 'view_site_sections', 'create' => 'create_site_sections', 'update' => 'edit_site_sections', 'delete' => 'delete_site_sections'],
        'SmsTemplateResource' => ['view' => 'view_sms_templates', 'create' => 'create_sms_templates', 'update' => 'edit_sms_templates', 'delete' => 'delete_sms_templates'],
        'SubscriptionResource' => ['view' => 'view_subscriptions'],
        'SupplierResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance', 'delete' => 'manage_finance'],
        'UserResource' => ['view' => 'view_users', 'create' => 'create_users', 'update' => 'edit_users', 'delete' => 'delete_users'],
        'VatAdjustmentResource' => ['view' => 'view_finance', 'create' => 'manage_finance', 'update' => 'manage_finance'],
        'VatReturnResource' => ['view' => 'view_finance'],
        'UniversalCreditAssessmentPeriodResource' => ['view' => 'view_universal_credit_reporting', 'create' => 'manage_universal_credit_reporting', 'update' => 'manage_universal_credit_reporting'],
    ];

    private const PAGE_PERMISSIONS = [
        'AccountProfilePage' => 'access_admin_panel',
        'AiKnowledgePage' => 'view_ai_knowledge',
        'AiConversationsPage' => 'view_ai_conversations',
        'AiLearningPage' => 'review_ai_questions',
        'AccountingProfilePage' => 'view_finance',
        'AdminDashboard' => 'access_admin_panel',
        'AgedReceivablesPayablesPage' => 'view_finance',
        'CalculatorAdminPage' => 'manage_calculator',
        'BankReconciliationReportPage' => 'view_finance',
        'BalanceSheetPage' => 'view_finance',
        'CalendarPage' => 'view_calendar',
        'ConversionReportPage' => 'view_reports',
        'ExpenseReportPage' => 'view_finance',
        'GeneralLedgerPage' => 'view_finance',
        'HmrcConnectionPage' => 'view_finance',
        'ManagementCashFlowPage' => 'view_finance',
        'ProfitAndLossPage' => 'view_finance',
        'TrialBalancePage' => 'view_finance',
        'VatDetailPage' => 'view_finance',
        'IntegrationSettingsPage' => 'manage_settings',
        'JobManagerPage' => 'manage_settings',
        'LegalSettingsPage' => 'manage_settings',
        'NavbarSettingsPage' => 'manage_settings',
        'PaymentSettingsPage' => 'manage_settings',
        'PipelinePage' => 'manage_leads',
        'SitemapPage' => 'manage_settings',
        'TrackingSettingsPage' => 'manage_settings',
        'LogViewerPage' => 'manage_settings',
    ];

    public static function panelAccessPermission(): string
    {
        return self::PANEL_ACCESS_PERMISSION;
    }

    public static function resourcePermission(string $resourceClass, string $action): ?string
    {
        $permissions = self::RESOURCE_PERMISSIONS[class_basename($resourceClass)] ?? null;

        if (! is_array($permissions)) {
            return null;
        }

        return $permissions[$action]
            ?? match ($action) {
                'viewAny', 'view' => $permissions['view'] ?? null,
                'create' => $permissions['create'] ?? null,
                'update', 'reorder', 'replicate' => $permissions['update'] ?? null,
                'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => $permissions['delete'] ?? null,
                default => null,
            };
    }

    public static function pagePermission(string $pageClass): ?string
    {
        return self::PAGE_PERMISSIONS[class_basename($pageClass)] ?? null;
    }
}
