<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\FinancialAttachment;
use App\Models\User;
use App\Services\Finance\FinancialAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancialAttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Expense $expense;

    private FinancialAttachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Permission::findOrCreate('view_finance', 'web');
        $this->business = Business::create([
            'name' => 'Secure Files Ltd',
            'slug' => 'secure-files',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view_finance');
        $this->addMember($this->business, $this->user);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_invoice_number' => 'SEC-001',
            'issue_date' => '2026-11-04',
            'currency' => 'GBP',
            'items' => [[
                'description' => 'Security test purchase',
                'quantity' => 1,
                'unit_price' => 10,
                'tax_code_id' => $this->business->taxCodes()->where('code', 'S20')->value('id'),
            ]],
        ]);
        $this->attachment = app(FinancialAttachmentService::class)->store(
            $this->expense,
            UploadedFile::fake()->create('evidence.pdf', 10, 'application/pdf'),
            $this->user,
            'invoice',
        );
    }

    public function test_a_clean_pdf_can_be_previewed_with_private_security_headers(): void
    {
        $this->attachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);

        $response = $this->actingAs($this->user)->get(route(
            'admin.finance.attachments.preview',
            $this->attachment,
        ));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_a_clean_file_can_be_downloaded_as_an_attachment(): void
    {
        $this->attachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);

        $response = $this->actingAs($this->user)->get(route(
            'admin.finance.attachments.download',
            $this->attachment,
        ));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('evidence.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_pending_or_rejected_files_are_not_served(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.finance.attachments.download', $this->attachment))
            ->assertStatus(423);

        $this->attachment->update(['scan_status' => FinancialAttachment::SCAN_REJECTED]);

        $this->get(route('admin.finance.attachments.download', $this->attachment))
            ->assertStatus(423);
    }

    public function test_preview_rejects_non_pdf_and_non_image_files_but_download_remains_available(): void
    {
        $textAttachment = app(FinancialAttachmentService::class)->store(
            $this->expense,
            UploadedFile::fake()->createWithContent('notes.txt', 'legitimate accounting notes'),
            $this->user,
            'other',
        );
        $textAttachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);

        $this->actingAs($this->user)
            ->get(route('admin.finance.attachments.preview', $textAttachment))
            ->assertStatus(415);
        $this->get(route('admin.finance.attachments.download', $textAttachment))
            ->assertOk();
    }

    public function test_a_user_without_finance_permission_cannot_access_a_clean_file(): void
    {
        $this->attachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);
        $unauthorized = User::factory()->create();
        $this->addMember($this->business, $unauthorized);

        $this->actingAs($unauthorized)
            ->get(route('admin.finance.attachments.download', $this->attachment))
            ->assertForbidden();
    }

    public function test_another_tenant_cannot_discover_an_attachment_route(): void
    {
        $this->attachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);
        $otherBusiness = Business::create([
            'name' => 'Other Secure Files Ltd',
            'slug' => 'other-secure-files',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create();
        $otherUser->givePermissionTo('view_finance');
        $this->addMember($otherBusiness, $otherUser);

        $this->actingAs($otherUser)
            ->get(route('admin.finance.attachments.download', $this->attachment->id))
            ->assertNotFound();
    }

    public function test_tampered_file_content_fails_the_sha256_integrity_check(): void
    {
        $this->attachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);
        Storage::disk('financial')->put($this->attachment->path, 'tampered content');

        $this->actingAs($this->user)
            ->get(route('admin.finance.attachments.download', $this->attachment))
            ->assertStatus(409);
    }

    public function test_guests_are_redirected_before_attachment_lookup(): void
    {
        $this->get(route('admin.finance.attachments.download', $this->attachment))
            ->assertRedirect(route('login'));
    }

    private function addMember(Business $business, User $user): void
    {
        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }
}
