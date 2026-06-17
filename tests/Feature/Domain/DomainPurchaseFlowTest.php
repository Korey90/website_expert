<?php

namespace Tests\Feature\Domain;

use App\Actions\Domain\CancelDomainOrderAction;
use App\Actions\Domain\CreateDomainOrderAction;
use App\Actions\Domain\GenerateDomainInvoiceAction;
use App\Actions\Domain\ProcessDomainPaymentAction;
use App\Actions\Domain\RegisterDomainAction;
use App\Data\Domain\DomainRegistrationResult;
use App\Http\Controllers\StripeWebhookController;
use App\Jobs\RegisterDomainJob;
use App\Models\Business;
use App\Models\Client;
use App\Models\DomainOrder;
use App\Models\DomainPriceList;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Domain\DomainOrderService;
use App\Services\Domain\DomainPricingService;
use App\Services\Domain\DomainRegistrarInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

/**
 * Testuje pełny przepływ zakupu domeny:
 *   Tworzenie zamówienia → opłata → rejestracja → ukończenie / niepowodzenie.
 *
 * Stripe i OpenProvider są mockowane — testy nie wymagają zewnętrznych kluczy API.
 *
 * Uruchomienie:
 *   php artisan test --filter=DomainPurchaseFlowTest
 */
class DomainPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $businessId;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->admin = User::where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->businessId = Business::where('is_active', true)->first()->id;

        DomainPriceList::insert([
            [
                'tld' => '.com',
                'register_price' => 10.00,
                'renew_price' => 12.00,
                'transfer_price' => 10.00,
                'currency' => 'GBP',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tld' => '.com',
                'register_price' => 50.00,
                'renew_price' => 60.00,
                'transfer_price' => 50.00,
                'currency' => 'PLN',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tld' => '.co.uk',
                'register_price' => 8.00,
                'renew_price' => 9.00,
                'transfer_price' => null,
                'currency' => 'GBP',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function orderData(array $overrides = []): array
    {
        return array_merge([
            'domain_name' => 'testdomain',
            'tld' => '.com',
            'action' => 'register',
            'years' => 1,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@test.example',
            'phone' => '+44 7700 900123',
            'address_line1' => '123 Test Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'country_code' => 'GB',
        ], $overrides);
    }

    private function paidOrder(string $domain = 'testpaid', string $tld = '.com'): DomainOrder
    {
        return DomainOrder::create([
            'business_id' => $this->businessId,
            'created_by' => $this->admin->id,
            'domain_name' => $domain,
            'tld' => $tld,
            'full_domain' => $domain.$tld,
            'years' => 1,
            'action' => 'register',
            'status' => 'paid',
            'retail_price' => 10.00,
            'currency' => 'GBP',
        ]);
    }

    // ── CreateDomainOrderAction ───────────────────────────────────────────────

    public function test_create_order_sets_pending_payment_status(): void
    {
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);

        $this->assertSame('pending_payment', $order->status);
        $this->assertDatabaseHas('domain_orders', [
            'domain_name' => 'testdomain',
            'tld' => '.com',
            'full_domain' => 'testdomain.com',
            'status' => 'pending_payment',
            'years' => 1,
        ]);
    }

    public function test_create_order_creates_registrant_contact(): void
    {
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);

        $this->assertDatabaseHas('domain_contacts', [
            'domain_order_id' => $order->id,
            'type' => 'registrant',
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@test.example',
            'country_code' => 'GB',
        ]);
    }

    public function test_create_order_calculates_retail_price_correctly(): void
    {
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute(
            $this->orderData(['tld' => '.com', 'years' => 2]),
            null
        );

        $this->assertSame('20.00', $order->retail_price);
    }

    public function test_create_order_uses_locale_currency_when_domain_price_exists(): void
    {
        app()->setLocale('pl');
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute(
            $this->orderData(['tld' => '.com', 'years' => 2]),
            null
        );

        $this->assertSame('PLN', $order->currency);
        $this->assertSame('100.00', $order->retail_price);
    }

    public function test_create_order_calculates_multi_year_co_uk_price(): void
    {
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute(
            $this->orderData(['tld' => '.co.uk', 'years' => 3]),
            null
        );

        $this->assertSame('24.00', $order->retail_price);
        $this->assertSame('.co.uk', $order->tld);
    }

    public function test_create_order_logs_order_created_event(): void
    {
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);

        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type' => 'order_created',
        ]);
    }

    // ── ProcessDomainPaymentAction ────────────────────────────────────────────

    public function test_payment_action_throws_when_stripe_not_configured(): void
    {
        $this->actingAs($this->admin);
        config(['services.stripe.secret' => '']);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stripe payments are not configured.');

        app(ProcessDomainPaymentAction::class)->execute(
            $order,
            'https://example.com/success',
            'https://example.com/cancel'
        );
    }

    // ── RegisterDomainAction ──────────────────────────────────────────────────

    public function test_register_action_dispatches_job_for_paid_order(): void
    {
        Queue::fake();
        $this->actingAs($this->admin);

        $order = $this->paidOrder('queuetest');

        app(RegisterDomainAction::class)->execute($order);

        Queue::assertPushed(RegisterDomainJob::class);
    }

    public function test_register_action_throws_for_pending_payment_order(): void
    {
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);

        $this->expectException(\InvalidArgumentException::class);

        app(RegisterDomainAction::class)->execute($order);
    }

    // ── CancelDomainOrderAction ───────────────────────────────────────────────

    public function test_can_cancel_pending_payment_order(): void
    {
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);
        $cancelled = app(CancelDomainOrderAction::class)->execute($order, 'Customer request');

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type' => 'order_cancelled',
        ]);
    }

    public function test_can_cancel_paid_order(): void
    {
        $this->actingAs($this->admin);

        $order = $this->paidOrder('cancelpaid');
        $cancelled = app(CancelDomainOrderAction::class)->execute($order, 'Admin cancellation');

        $this->assertSame('cancelled', $cancelled->status);
    }

    public function test_cancel_throws_for_completed_order(): void
    {
        $this->actingAs($this->admin);

        $order = DomainOrder::create([
            'business_id' => $this->businessId,
            'created_by' => $this->admin->id,
            'domain_name' => 'completed',
            'tld' => '.com',
            'full_domain' => 'completed.com',
            'years' => 1,
            'action' => 'register',
            'status' => 'completed',
            'retail_price' => 10.00,
            'currency' => 'GBP',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(CancelDomainOrderAction::class)->execute($order);
    }

    public function test_cancel_throws_for_registering_order(): void
    {
        $this->actingAs($this->admin);

        $order = DomainOrder::create([
            'business_id' => $this->businessId,
            'created_by' => $this->admin->id,
            'domain_name' => 'registering',
            'tld' => '.com',
            'full_domain' => 'registering.com',
            'years' => 1,
            'action' => 'register',
            'status' => 'registering',
            'retail_price' => 10.00,
            'currency' => 'GBP',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(CancelDomainOrderAction::class)->execute($order);
    }

    // ── DomainOrderService::markAsPaid ────────────────────────────────────────

    public function test_mark_as_paid_updates_order_status_and_stores_intent(): void
    {
        Queue::fake();
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);
        $paid = app(DomainOrderService::class)->markAsPaid($order, 'pi_test_1234');

        $this->assertSame('paid', $paid->status);
        $this->assertSame('pi_test_1234', $paid->stripe_payment_intent_id);
    }

    public function test_mark_as_paid_dispatches_register_domain_job(): void
    {
        Queue::fake();
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);
        app(DomainOrderService::class)->markAsPaid($order, 'pi_dispatch_test');

        Queue::assertPushed(RegisterDomainJob::class);
    }

    // ── RegisterDomainJob (mocked registrar) ──────────────────────────────────

    public function test_register_domain_job_completes_order_on_success(): void
    {
        Notification::fake();
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);
        $order->update(['status' => 'paid', 'stripe_payment_intent_id' => 'pi_mock']);

        $mockRegistrar = Mockery::mock(DomainRegistrarInterface::class);
        $mockRegistrar->shouldReceive('register')->once()->andReturn(
            DomainRegistrationResult::success(
                providerId: '9876543',
                registeredAt: now(),
                expiresAt: now()->addYear(),
            )
        );
        $this->app->instance(DomainRegistrarInterface::class, $mockRegistrar);

        (new RegisterDomainJob($order->fresh()))->handle(
            $mockRegistrar,
            app(DomainOrderService::class),
            app(DomainPricingService::class),
        );

        $this->assertDatabaseHas('domain_orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('domains', [
            'full_domain' => 'testdomain.com',
            'status' => 'active',
            'provider_domain_id' => '9876543',
        ]);
        $this->assertDatabaseHas('domain_renewals', ['status' => 'pending', 'currency' => 'GBP']);
        $this->assertDatabaseHas('domain_events', ['domain_order_id' => $order->id, 'type' => 'registered']);
    }

    public function test_register_domain_job_fails_order_on_registrar_error_result(): void
    {
        Notification::fake();
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);
        $order->update(['status' => 'paid']);

        $mockRegistrar = Mockery::mock(DomainRegistrarInterface::class);
        $mockRegistrar->shouldReceive('register')->once()->andReturn(
            DomainRegistrationResult::failure('Domain already registered')
        );
        $this->app->instance(DomainRegistrarInterface::class, $mockRegistrar);

        (new RegisterDomainJob($order->fresh()))->handle(
            $mockRegistrar,
            app(DomainOrderService::class),
            app(DomainPricingService::class),
        );

        $this->assertDatabaseHas('domain_orders', ['id' => $order->id, 'status' => 'failed']);
        $this->assertDatabaseMissing('domains', ['full_domain' => 'testdomain.com']);
        $this->assertDatabaseHas('domain_events', ['domain_order_id' => $order->id, 'type' => 'registration_failed']);
    }

    public function test_register_domain_job_fails_order_on_registrar_exception(): void
    {
        Notification::fake();
        $this->actingAs($this->admin);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), null);
        $order->update(['status' => 'paid']);

        $mockRegistrar = Mockery::mock(DomainRegistrarInterface::class);
        $mockRegistrar->shouldReceive('register')->once()
            ->andThrow(new \RuntimeException('API connection timeout'));
        $this->app->instance(DomainRegistrarInterface::class, $mockRegistrar);

        (new RegisterDomainJob($order->fresh()))->handle(
            $mockRegistrar,
            app(DomainOrderService::class),
            app(DomainPricingService::class),
        );

        $this->assertDatabaseHas('domain_orders', ['id' => $order->id, 'status' => 'failed']);
    }

    // ── Stripe webhook: checkout.session.completed → invoice paid ─────────────

    public function test_stripe_webhook_marks_domain_order_invoice_as_paid(): void
    {
        Queue::fake();
        $this->actingAs($this->admin);

        // Invoice requires a client — create one for this test
        $client = Client::create([
            'business_id' => $this->businessId,
            'company_name' => 'Webhook Test Ltd',
            'primary_contact_email' => 'webhook@test.example',
        ]);

        $order = app(CreateDomainOrderAction::class)->execute($this->orderData(), $client);
        $invoice = app(GenerateDomainInvoiceAction::class)->execute($order);

        $this->assertSame('draft', $invoice->status);

        // Build a fake Stripe Checkout Session object directly (no HTTP, no signature)
        $sessionData = [
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'payment_intent' => 'pi_test_webhook_123',
            'amount_total' => (int) round($order->retail_price * 1.2 * 100),
            'currency' => 'gbp',
            'payment_status' => 'paid',
            'metadata' => ['domain_order_id' => (string) $order->id],
        ];
        $session = Session::constructFrom($sessionData);

        // Call the private handler directly — bypasses HTTP routing and signature verification
        $controller = app(StripeWebhookController::class);
        $reflection = new \ReflectionMethod($controller, 'handleCheckoutSessionCompleted');
        $reflection->setAccessible(true);
        $reflection->invoke($controller, $session);

        // Order should be paid
        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        // Invoice should be paid
        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        // Payment record should exist
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'status' => 'completed',
            'stripe_payment_intent_id' => 'pi_test_webhook_123',
        ]);
    }
}
