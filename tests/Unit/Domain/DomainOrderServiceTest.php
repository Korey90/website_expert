<?php

namespace Tests\Unit\Domain;

use App\Jobs\RegisterDomainJob;
use App\Models\Business;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\User;
use App\Services\Domain\DomainOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Testy jednostkowe dla DomainOrderService.
 *
 * Uruchomienie:
 *   php artisan test --filter=DomainOrderServiceTest
 */
class DomainOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainOrderService $service;
    private string             $businessId;
    private int                $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->service    = app(DomainOrderService::class);
        $this->businessId = Business::where('is_active', true)->first()->id;
        $this->userId     = User::first()->id;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeOrderData(array $overrides = []): array
    {
        return array_merge([
            'business_id'  => $this->businessId,
            'created_by'   => $this->userId,
            'domain_name'  => 'testdomain',
            'tld'          => '.com',
            'full_domain'  => 'testdomain.com',
            'years'        => 1,
            'action'       => 'register',
            'retail_price' => 10.00,
            'currency'     => 'GBP',
        ], $overrides);
    }

    private function makeOrder(array $overrides = []): DomainOrder
    {
        return DomainOrder::create(array_merge([
            'business_id'  => $this->businessId,
            'created_by'   => $this->userId,
            'domain_name'  => 'testdomain',
            'tld'          => '.com',
            'full_domain'  => 'testdomain.com',
            'years'        => 1,
            'action'       => 'register',
            'status'       => 'pending_payment',
            'retail_price' => 10.00,
            'currency'     => 'GBP',
        ], $overrides));
    }

    // ── createOrder ───────────────────────────────────────────────────────────

    public function test_create_order_sets_pending_payment_status(): void
    {
        $order = $this->service->createOrder($this->makeOrderData());

        $this->assertSame('pending_payment', $order->status);
        $this->assertDatabaseHas('domain_orders', ['id' => $order->id, 'status' => 'pending_payment']);
    }

    public function test_create_order_normalises_domain_to_lowercase(): void
    {
        $order = $this->service->createOrder($this->makeOrderData([
            'domain_name' => 'UPPERCASE',
            'tld'         => '.COM',
            'full_domain' => 'UPPERCASE.COM',
        ]));

        $this->assertSame('uppercase', $order->domain_name);
        $this->assertSame('.com', $order->tld);
        $this->assertSame('uppercase.com', $order->full_domain);
    }

    public function test_create_order_logs_order_created_event(): void
    {
        $order = $this->service->createOrder($this->makeOrderData());

        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type'            => 'order_created',
        ]);
    }

    public function test_create_order_stores_correct_years(): void
    {
        $order = $this->service->createOrder($this->makeOrderData(['years' => 3]));

        $this->assertSame(3, $order->years);
    }

    // ── markAsPaid ────────────────────────────────────────────────────────────

    public function test_mark_as_paid_updates_status_to_paid(): void
    {
        Queue::fake();
        $order  = $this->makeOrder();
        $result = $this->service->markAsPaid($order, 'pi_test_123');

        $this->assertSame('paid', $result->status);
    }

    public function test_mark_as_paid_stores_stripe_payment_intent_id(): void
    {
        Queue::fake();
        $order  = $this->makeOrder();
        $result = $this->service->markAsPaid($order, 'pi_intent_abc');

        $this->assertSame('pi_intent_abc', $result->stripe_payment_intent_id);
    }

    public function test_mark_as_paid_dispatches_register_domain_job(): void
    {
        Queue::fake();
        $order = $this->makeOrder();
        $this->service->markAsPaid($order, 'pi_dispatch');

        Queue::assertPushed(RegisterDomainJob::class);
    }

    public function test_mark_as_paid_logs_order_paid_event(): void
    {
        Queue::fake();
        $order = $this->makeOrder();
        $this->service->markAsPaid($order, 'pi_event');

        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type'            => 'order_paid',
        ]);
    }

    // ── markAsRegistering ─────────────────────────────────────────────────────

    public function test_mark_as_registering_updates_status(): void
    {
        $order  = $this->makeOrder(['status' => 'paid']);
        $result = $this->service->markAsRegistering($order);

        $this->assertSame('registering', $result->status);
    }

    public function test_mark_as_registering_logs_event(): void
    {
        $order = $this->makeOrder(['status' => 'paid']);
        $this->service->markAsRegistering($order);

        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type'            => 'registration_started',
        ]);
    }

    // ── completeOrder ─────────────────────────────────────────────────────────

    public function test_complete_order_creates_active_domain_record(): void
    {
        $order  = $this->makeOrder(['status' => 'paid']);
        $domain = $this->service->completeOrder(
            order:            $order,
            providerDomainId: '12345',
            registeredAt:     now(),
            expiresAt:        now()->addYear(),
        );

        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertSame('active', $domain->status);
        $this->assertSame('testdomain.com', $domain->full_domain);
        $this->assertSame('12345', $domain->provider_domain_id);
    }

    public function test_complete_order_sets_completed_status_and_timestamp(): void
    {
        $order = $this->makeOrder(['status' => 'paid']);
        $this->service->completeOrder(
            order:            $order,
            providerDomainId: '99999',
            registeredAt:     now(),
            expiresAt:        now()->addYear(),
        );

        $fresh = $order->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_complete_order_logs_registered_event(): void
    {
        $order = $this->makeOrder(['status' => 'paid']);
        $this->service->completeOrder(
            order:            $order,
            providerDomainId: '77777',
            registeredAt:     now(),
            expiresAt:        now()->addYear(),
        );

        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type'            => 'registered',
        ]);
    }

    // ── failOrder ─────────────────────────────────────────────────────────────

    public function test_fail_order_sets_failed_status(): void
    {
        $order  = $this->makeOrder(['status' => 'registering']);
        $result = $this->service->failOrder($order, 'Registrar timeout');

        $this->assertSame('failed', $result->status);
    }

    public function test_fail_order_logs_registration_failed_event(): void
    {
        $order = $this->makeOrder(['status' => 'registering']);
        $this->service->failOrder($order, 'Timeout error');

        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type'            => 'registration_failed',
        ]);
    }

    // ── cancelOrder ───────────────────────────────────────────────────────────

    public function test_cancel_order_sets_cancelled_status(): void
    {
        $order  = $this->makeOrder();
        $result = $this->service->cancelOrder($order, 'No longer needed');

        $this->assertSame('cancelled', $result->status);
    }

    public function test_cancel_order_logs_order_cancelled_event(): void
    {
        $order = $this->makeOrder();
        $this->service->cancelOrder($order, 'Customer changed mind');

        $this->assertDatabaseHas('domain_events', [
            'domain_order_id' => $order->id,
            'type'            => 'order_cancelled',
        ]);
    }

    // ── getStalePendingOrders ─────────────────────────────────────────────────

    public function test_get_stale_pending_orders_returns_orders_older_than_one_hour(): void
    {
        $recent = $this->makeOrder(['domain_name' => 'recent', 'full_domain' => 'recent.com']);

        $old = $this->makeOrder(['domain_name' => 'old', 'full_domain' => 'old.com']);
        $old->created_at = now()->subHours(2);
        $old->save();

        $stale = $this->service->getStalePendingOrders();

        $this->assertTrue($stale->contains('id', $old->id));
        $this->assertFalse($stale->contains('id', $recent->id));
    }

    public function test_get_stale_pending_orders_excludes_non_pending_statuses(): void
    {
        $paid = $this->makeOrder(['status' => 'paid', 'domain_name' => 'paidold', 'full_domain' => 'paidold.com']);
        $paid->created_at = now()->subHours(2);
        $paid->save();

        $stale = $this->service->getStalePendingOrders();

        $this->assertFalse($stale->contains('id', $paid->id));
    }
}
