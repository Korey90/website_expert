<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Jobs\ScanFinancialAttachmentJob;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\FinancialAttachment;
use App\Models\User;
use App\Services\Finance\FinancialAttachmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialAttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Queue::fake();
        $this->business = Business::create([
            'name' => 'Attachment Books Ltd',
            'slug' => 'attachment-books',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_invoice_number' => 'ATT-001',
            'issue_date' => '2026-11-04',
            'currency' => 'GBP',
            'items' => [[
                'description' => 'Office supplies',
                'quantity' => 1,
                'unit_price' => 25,
                'tax_code_id' => $this->business->taxCodes()->where('code', 'S20')->value('id'),
            ]],
        ]);
    }

    public function test_it_stores_financial_evidence_on_a_private_disk_with_integrity_metadata(): void
    {
        $file = UploadedFile::fake()->create('supplier-bill.pdf', 100, 'application/pdf');
        $expectedHash = hash_file('sha256', $file->getRealPath());

        $attachment = app(FinancialAttachmentService::class)->store(
            $this->expense,
            $file,
            $this->user,
            'invoice',
        );

        Storage::disk('financial')->assertExists($attachment->path);
        $this->assertSame($this->business->id, $attachment->business_id);
        $this->assertSame($this->user->id, $attachment->uploaded_by);
        $this->assertSame('financial', $attachment->disk);
        $this->assertSame('supplier-bill.pdf', $attachment->original_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame($expectedHash, $attachment->sha256);
        $this->assertSame(FinancialAttachment::SCAN_PENDING, $attachment->scan_status);
        $this->assertSame(today()->addYears(6)->toDateString(), $attachment->retention_until->toDateString());
        $this->assertStringStartsWith($this->business->id.'/expense/'.$this->expense->id.'/', $attachment->path);
        $this->assertStringNotContainsString('supplier-bill', $attachment->path);
        $this->assertTrue($this->expense->attachments()->whereKey($attachment->id)->exists());
        $this->assertNotNull($attachment->scan_requested_at);
        Queue::assertPushed(
            ScanFinancialAttachmentJob::class,
            fn (ScanFinancialAttachmentJob $job): bool => $job->attachmentId === $attachment->id,
        );
    }

    public function test_it_rejects_a_duplicate_file_for_the_same_financial_record(): void
    {
        $first = UploadedFile::fake()->createWithContent('first.pdf', 'same financial evidence');
        $second = UploadedFile::fake()->createWithContent('renamed.pdf', 'same financial evidence');
        $service = app(FinancialAttachmentService::class);

        $service->store($this->expense, $first, $this->user, 'invoice');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already attached');

        try {
            $service->store($this->expense, $second, $this->user, 'invoice');
        } finally {
            $this->assertDatabaseCount('financial_attachments', 1);
            $this->assertCount(1, Storage::disk('financial')->allFiles());
        }
    }

    public function test_it_rejects_unsupported_mime_types_and_document_types(): void
    {
        $service = app(FinancialAttachmentService::class);

        try {
            $service->store(
                $this->expense,
                UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
                $this->user,
                'receipt',
            );
            $this->fail('An executable attachment should be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('MIME', $exception->getMessage());
        }

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('document type');

        try {
            $service->store(
                $this->expense,
                UploadedFile::fake()->create('bill.pdf', 10, 'application/pdf'),
                $this->user,
                'malware',
            );
        } finally {
            $this->assertDatabaseCount('financial_attachments', 0);
            $this->assertSame([], Storage::disk('financial')->allFiles());
        }
    }

    public function test_it_rejects_files_above_the_configured_limit(): void
    {
        config()->set('finance.attachments.max_bytes', 1024);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('maximum allowed size');

        app(FinancialAttachmentService::class)->store(
            $this->expense,
            UploadedFile::fake()->create('too-large.pdf', 2, 'application/pdf'),
            $this->user,
            'invoice',
        );
    }

    public function test_an_actor_from_another_tenant_cannot_attach_a_file(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Attachment Ltd',
            'slug' => 'other-attachment',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create();
        BusinessUser::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('does not belong');

        app(FinancialAttachmentService::class)->store(
            $this->expense,
            UploadedFile::fake()->create('cross-tenant.pdf', 10, 'application/pdf'),
            $otherUser,
            'invoice',
        );
    }

    public function test_attachment_queries_are_scoped_to_the_authenticated_users_tenant(): void
    {
        $attachment = app(FinancialAttachmentService::class)->store(
            $this->expense,
            UploadedFile::fake()->create('visible.pdf', 10, 'application/pdf'),
            $this->user,
            'invoice',
        );

        $otherBusiness = Business::create([
            'name' => 'Scoped Attachment Ltd',
            'slug' => 'scoped-attachment',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create();
        BusinessUser::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->actingAs($otherUser);

        $this->assertNull(FinancialAttachment::find($attachment->id));
        $this->assertNotNull(FinancialAttachment::withoutGlobalScopes()->find($attachment->id));
    }
}
