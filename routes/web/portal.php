<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Portal\ContractController as PortalContractController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\LeadController as PortalLeadController;
use App\Http\Controllers\Portal\InvoiceController as PortalInvoiceController;
use App\Http\Controllers\Portal\NotificationController as PortalNotificationController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\PaymentResultController as PortalPaymentResultController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\Portal\QuoteController as PortalQuoteController;
use App\Http\Controllers\Domain\DomainOrderController;
use App\Http\Controllers\Domain\PublicDomainController;
use App\Http\Controllers\Portal\DomainController as PortalDomainController;
use App\Http\Controllers\Portal\DomainDnsController;
use App\Http\Controllers\Portal\DomainOrderController as PortalDomainOrderController;
use App\Http\Controllers\Portal\DomainCheckoutController as PortalDomainCheckoutController;


Route::middleware('auth')->group(function () {

        // Client Portal
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');

        Route::middleware('portal.client')->group(function () {
            Route::get('/projects', [PortalProjectController::class, 'index'])->name('projects');
            Route::get('/projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
            Route::post('/projects/{project}/messages', [PortalProjectController::class, 'storeMessage'])->name('messages.store');

            Route::get('/invoices', [PortalInvoiceController::class, 'index'])->name('invoices');
            Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/pay', [PortalPaymentController::class, 'selectMethod'])->name('invoices.pay');
            Route::post('/invoices/{invoice}/pay/stripe', [PortalPaymentController::class, 'stripeCheckout'])->name('invoices.pay.stripe');
            Route::post('/invoices/{invoice}/pay/payu', [PortalPaymentController::class, 'payuInitiate'])->name('invoices.pay.payu');
            Route::get('/invoices/{invoice}/payment-result', [PortalPaymentResultController::class, 'show'])->name('invoices.payment-result');

            Route::get('/quotes', [PortalQuoteController::class, 'index'])->name('quotes');
            Route::get('/quotes/{quote}', [PortalQuoteController::class, 'show'])->name('quotes.show');
            Route::post('/quotes/{quote}/accept', [PortalQuoteController::class, 'accept'])->name('quotes.accept');
            Route::post('/quotes/{quote}/reject', [PortalQuoteController::class, 'reject'])->name('quotes.reject');

            Route::get('/contracts', [PortalContractController::class, 'index'])->name('contracts');
            Route::get('/contracts/{contract}', [PortalContractController::class, 'show'])->name('contracts.show');
            Route::post('/contracts/{contract}/sign', [PortalContractController::class, 'sign'])->name('contracts.sign');

            Route::get('/settings/notifications', [PortalNotificationController::class, 'settings'])->name('settings.notifications');
            Route::post('/settings/notifications', [PortalNotificationController::class, 'updateSettings'])->name('settings.notifications.update');

            // Domain management + ordering flow
            Route::prefix('domains')->name('domains.')->group(function () {
                Route::get('/', [PortalDomainController::class, 'index'])->name('index');
                Route::get('/order', [PortalDomainController::class, 'order'])->name('order');
                Route::post('/order', [PortalDomainOrderController::class, 'store'])->name('order.store');
                Route::get('/order/{order}/checkout', [PortalDomainCheckoutController::class, 'show'])->name('checkout');
                Route::post('/order/{order}/checkout', [PortalDomainCheckoutController::class, 'pay'])->name('pay');
                Route::get('/order/{order}/result', [PortalDomainCheckoutController::class, 'result'])->name('result');
                Route::get('/{domain}', [PortalDomainController::class, 'show'])->name('show');
                Route::put('/{domain}/nameservers', [PortalDomainController::class, 'updateNameservers'])->name('nameservers.update');
                Route::get('/{domain}/dns', [DomainDnsController::class, 'index'])->name('dns.index');
                Route::post('/{domain}/dns', [DomainDnsController::class, 'store'])->name('dns.store');
                Route::put('/{domain}/dns/{recordId}', [DomainDnsController::class, 'update'])->name('dns.update');
                Route::delete('/{domain}/dns/{recordId}', [DomainDnsController::class, 'destroy'])->name('dns.destroy');
            });
        });

        Route::get('/leads/{lead}', [PortalLeadController::class, 'show'])
            ->middleware('portal.workspace:leads')
            ->name('leads.show');

        Route::middleware('portal.workspace:billing')->group(function () {
            Route::get('/billing', [\App\Http\Controllers\Portal\BillingController::class, 'index'])->name('billing');
            Route::post('/billing/checkout/{plan}', [\App\Http\Controllers\Portal\BillingController::class, 'checkout'])->name('billing.checkout');
            Route::get('/billing/success', [\App\Http\Controllers\Portal\BillingController::class, 'success'])->name('billing.success');
            Route::post('/billing/portal', [\App\Http\Controllers\Portal\BillingController::class, 'portal'])->name('billing.portal');
        });
    });

});