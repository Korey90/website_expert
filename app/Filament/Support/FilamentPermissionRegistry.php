<?php

namespace App\Filament\Support;

class FilamentPermissionRegistry
{
    private const PANEL_ACCESS_PERMISSION = 'access_admin_panel';

    private const RESOURCE_PERMISSIONS = [
        'AutomationLogResource' => ['view' => 'view_automation_logs', 'delete' => 'delete_automation_logs'],
        'AutomationRuleResource' => ['view' => 'view_automations', 'create' => 'create_automations', 'update' => 'edit_automations', 'delete' => 'delete_automations'],
        'AutomationTriggerResource' => ['view' => 'view_automation_triggers', 'create' => 'create_automation_triggers', 'update' => 'edit_automation_triggers', 'delete' => 'delete_automation_triggers'],
        'BriefingResource' => ['view' => 'view_briefings', 'create' => 'create_briefings', 'update' => 'edit_briefings', 'delete' => 'delete_briefings'],
        'BriefingTemplateResource' => ['view' => 'view_briefing_templates', 'create' => 'create_briefing_templates', 'update' => 'edit_briefing_templates', 'delete' => 'delete_briefing_templates'],
        'BusinessResource' => ['view' => 'view_businesses', 'update' => 'edit_businesses'],
        'CalculatorPricingResource' => ['view' => 'manage_calculator', 'create' => 'manage_calculator', 'update' => 'manage_calculator', 'delete' => 'manage_calculator'],
        'CalculatorStepsResource' => ['view' => 'manage_calculator', 'create' => 'manage_calculator', 'update' => 'manage_calculator', 'delete' => 'manage_calculator'],
        'CalculatorStringsResource' => ['view' => 'manage_calculator', 'create' => 'manage_calculator', 'update' => 'manage_calculator', 'delete' => 'manage_calculator'],
        'ClientResource' => ['view' => 'view_clients', 'create' => 'create_clients', 'update' => 'edit_clients', 'delete' => 'delete_clients'],
        'ContractResource' => ['view' => 'view_contracts', 'create' => 'create_contracts', 'update' => 'edit_contracts', 'delete' => 'delete_contracts'],
        'ContractTemplateResource' => ['view' => 'view_contract_templates', 'create' => 'create_contract_templates', 'update' => 'edit_contract_templates', 'delete' => 'delete_contract_templates'],
        'EmailTemplateResource' => ['view' => 'view_email_templates', 'create' => 'create_email_templates', 'update' => 'edit_email_templates', 'delete' => 'delete_email_templates'],
        'InvoiceResource' => ['view' => 'view_invoices', 'create' => 'create_invoices', 'update' => 'edit_invoices', 'delete' => 'delete_invoices'],
        'LandingPageResource' => ['view' => 'view_landing_pages', 'create' => 'manage_landing_pages', 'update' => 'manage_landing_pages', 'delete' => 'manage_landing_pages'],
        'LeadResource' => ['view' => 'view_leads', 'create' => 'create_leads', 'update' => 'edit_leads', 'delete' => 'delete_leads'],
        'NotificationResource' => ['view' => 'view_notifications', 'create' => 'create_notifications', 'delete' => 'delete_notifications'],
        'PageResource' => ['view' => 'view_pages', 'create' => 'create_pages', 'update' => 'edit_pages', 'delete' => 'delete_pages'],
        'PaymentResource' => ['view' => 'view_payments', 'create' => 'create_payments', 'update' => 'edit_payments', 'delete' => 'delete_payments'],
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
        'ServiceItemResource' => ['view' => 'view_services', 'create' => 'create_services', 'update' => 'edit_services', 'delete' => 'delete_services'],
        'SessionResource' => ['view' => 'view_sessions', 'delete' => 'revoke_sessions'],
        'SiteSectionResource' => ['view' => 'view_site_sections', 'create' => 'create_site_sections', 'update' => 'edit_site_sections', 'delete' => 'delete_site_sections'],
        'SmsTemplateResource' => ['view' => 'view_sms_templates', 'create' => 'create_sms_templates', 'update' => 'edit_sms_templates', 'delete' => 'delete_sms_templates'],
        'SubscriptionResource' => ['view' => 'view_subscriptions'],
        'UserResource' => ['view' => 'view_users', 'create' => 'create_users', 'update' => 'edit_users', 'delete' => 'delete_users'],
    ];

    private const PAGE_PERMISSIONS = [
        'CalculatorAdminPage' => 'manage_calculator',
        'ConversionReportPage' => 'view_reports',
        'IntegrationSettingsPage' => 'manage_settings',
        'LegalSettingsPage' => 'manage_settings',
        'PaymentSettingsPage' => 'manage_settings',
        'PipelinePage' => 'manage_leads',
        'SitemapPage' => 'manage_settings',
        'TrackingSettingsPage' => 'manage_settings',
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