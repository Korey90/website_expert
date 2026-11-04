<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\GenerateDocumentNumberAction;
use App\Models\Business;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GenerateDocumentNumberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequences_are_isolated_by_tenant_type_and_year(): void
    {
        $businessA = $this->business('Agency A', 'agency-a');
        $businessB = $this->business('Agency B', 'agency-b');
        $action = app(GenerateDocumentNumberAction::class);
        $in2026 = CarbonImmutable::parse('2026-11-04');

        $this->assertSame('INV-2026-001', $action->execute($businessA->id, 'invoice', $in2026));
        $this->assertSame('INV-2026-002', $action->execute($businessA->id, 'invoice', $in2026));
        $this->assertSame('QUOT-2026-001', $action->execute($businessA->id, 'quote', $in2026));
        $this->assertSame('INV-2026-001', $action->execute($businessB->id, 'invoice', $in2026));
        $this->assertSame('INV-2027-001', $action->execute($businessA->id, 'invoice', $in2026->addYear()));
    }

    public function test_new_sequence_continues_after_an_existing_document_number(): void
    {
        $business = $this->business('Existing Books', 'existing-books');
        $user = User::factory()->create();
        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Customer',
            'status' => 'active',
        ]);
        Invoice::create([
            'business_id' => $business->id,
            'number' => 'INV-2026-014',
            'client_id' => $client->id,
            'created_by' => $user->id,
            'status' => 'draft',
            'issue_date' => '2026-11-04',
            'due_date' => '2026-12-04',
        ]);

        $number = app(GenerateDocumentNumberAction::class)->execute(
            $business->id,
            'invoice',
            CarbonImmutable::parse('2026-11-04'),
        );

        $this->assertSame('INV-2026-015', $number);
    }

    public function test_a_batch_of_numbers_is_unique_and_monotonic(): void
    {
        $business = $this->business('Sequence Ltd', 'sequence');
        $action = app(GenerateDocumentNumberAction::class);
        $date = CarbonImmutable::parse('2026-11-04');

        $numbers = collect(range(1, 25))
            ->map(fn () => $action->execute($business->id, 'invoice', $date));

        $this->assertCount(25, $numbers->unique());
        $this->assertSame('INV-2026-001', $numbers->first());
        $this->assertSame('INV-2026-025', $numbers->last());
    }

    public function test_unsupported_document_type_is_rejected(): void
    {
        $business = $this->business('Sequence Ltd', 'sequence');

        $this->expectException(InvalidArgumentException::class);

        app(GenerateDocumentNumberAction::class)->execute($business->id, 'receipt');
    }

    private function business(string $name, string $slug): Business
    {
        return Business::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
    }
}
