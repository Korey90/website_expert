<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateFixedAssetAction;
use App\Actions\Finance\CreateFixedAssetCategoryAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\UpdateFixedAssetAction;
use App\Actions\Finance\UpdateFixedAssetCategoryAction;
use App\Filament\Resources\FixedAssetCategoryResource;
use App\Filament\Resources\FixedAssetResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\LedgerAccount;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FixedAssetRegistryTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = $this->createBusiness('Asset Books', 'asset-books');
        $this->user = $this->createUserFor($this->business, ['access_admin_panel', 'view_finance', 'manage_finance']);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
    }

    public function test_it_creates_and_updates_categories_and_draft_assets_without_posting_to_the_ledger(): void
    {
        $category = app(CreateFixedAssetCategoryAction::class)->execute(
            $this->business,
            $this->user,
            $this->categoryData(),
        );

        $this->assertSame('EQUIPMENT', $category->code);
        $this->assertSame('Equipment', $category->getTranslation('name', 'en'));
        $this->assertSame('Wyposażenie', $category->getTranslation('name', 'pl'));

        $asset = app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $category->id,
            'name' => 'Development workstation',
            'serial_number' => 'WS-001',
            'location' => 'Home office',
        ]);

        $this->assertMatchesRegularExpression('/^FA-\d{4}-001$/', $asset->number);
        $this->assertSame(FixedAsset::STATUS_DRAFT, $asset->status);
        $this->assertSame(FixedAssetCategory::METHOD_STRAIGHT_LINE, $asset->depreciation_method);
        $this->assertSame(36, $asset->useful_life_months);
        $this->assertSame('0.00', $asset->residual_value_percent);
        $this->assertSame($this->user->id, $asset->created_by);
        $this->assertDatabaseCount('journal_entries', 0);

        $category = app(UpdateFixedAssetCategoryAction::class)->execute($category, $this->user, [
            ...$this->categoryData(),
            'name' => ['en' => 'Computer equipment', 'pl' => 'Sprzęt komputerowy'],
            'default_useful_life_months' => 48,
        ]);
        $asset = app(UpdateFixedAssetAction::class)->execute($asset, $this->user, [
            'fixed_asset_category_id' => $category->id,
            'name' => 'Primary development workstation',
            'depreciation_method' => FixedAssetCategory::METHOD_REDUCING_BALANCE,
            'depreciation_rate_percent' => '25.00',
            'useful_life_months' => 24,
            'residual_value_percent' => '10.50',
        ]);

        $this->assertSame(48, $category->default_useful_life_months);
        $this->assertSame('Primary development workstation', $asset->name);
        $this->assertSame(FixedAssetCategory::METHOD_REDUCING_BALANCE, $asset->depreciation_method);
        $this->assertSame('25.00', $asset->depreciation_rate_percent);
        $this->assertSame('10.50', $asset->residual_value_percent);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_category_rejects_wrong_account_roles_and_cross_tenant_accounts(): void
    {
        $invalid = $this->categoryData();
        $invalid['asset_account_id'] = $this->account($this->business, '6200')->id;

        try {
            app(CreateFixedAssetCategoryAction::class)->execute($this->business, $this->user, $invalid);
            $this->fail('An expense account must not be accepted as a fixed asset account.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('asset/fixed_asset', $exception->getMessage());
        }

        $other = $this->createBusiness('Other Assets', 'other-assets');
        app(InitializeAccountingSetupAction::class)->execute($other);
        $invalid = $this->categoryData();
        $invalid['asset_account_id'] = $this->account($other, '1500')->id;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('same business');
        app(CreateFixedAssetCategoryAction::class)->execute($this->business, $this->user, $invalid);
    }

    public function test_asset_rejects_inactive_and_cross_tenant_categories(): void
    {
        $inactive = app(CreateFixedAssetCategoryAction::class)->execute(
            $this->business,
            $this->user,
            [...$this->categoryData(), 'is_active' => false],
        );

        foreach ([$inactive, $this->categoryForOtherTenant()] as $category) {
            try {
                app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
                    'fixed_asset_category_id' => $category->id,
                    'name' => 'Invalid asset',
                ]);
                $this->fail('An inactive or cross-tenant category must not be accepted.');
            } catch (DomainException $exception) {
                $this->assertStringContainsString('active category from the same business', $exception->getMessage());
            }
        }
    }

    public function test_categories_and_assets_are_tenant_isolated_in_models_policies_and_direct_urls(): void
    {
        $category = $this->createCategory();
        $asset = $this->createAsset($category);
        $foreignCategory = $this->categoryForOtherTenant();
        $foreignUser = $this->createUserFor($foreignCategory->business, ['access_admin_panel', 'view_finance', 'manage_finance']);
        $foreignAsset = app(CreateFixedAssetAction::class)->execute($foreignCategory->business, $foreignUser, [
            'fixed_asset_category_id' => $foreignCategory->id,
            'name' => 'Foreign asset',
        ]);

        $this->actingAs($this->user);

        $this->assertSame(1, FixedAssetCategory::count());
        $this->assertSame(1, FixedAsset::count());
        $this->assertTrue(Gate::forUser($this->user)->allows('view', $category));
        $this->assertTrue(Gate::forUser($this->user)->allows('update', $asset));
        $this->assertFalse(Gate::forUser($this->user)->allows('view', $foreignCategory));
        $this->assertFalse(Gate::forUser($this->user)->allows('view', $foreignAsset));

        $this->get(FixedAssetResource::getUrl('view', ['record' => $foreignAsset]))->assertNotFound();
        $this->get(FixedAssetCategoryResource::getUrl('edit', ['record' => $foreignCategory]))->assertNotFound();
    }

    public function test_finance_manager_can_use_pages_and_view_only_user_cannot_write(): void
    {
        $category = $this->createCategory();
        $asset = $this->createAsset($category);

        $this->actingAs($this->user);
        $this->get(FixedAssetCategoryResource::getUrl('index'))->assertOk()->assertSee('EQUIPMENT');
        $this->get(FixedAssetCategoryResource::getUrl('create'))->assertOk();
        $this->get(FixedAssetCategoryResource::getUrl('edit', ['record' => $category]))->assertOk();
        $this->get(FixedAssetResource::getUrl('index'))->assertOk()->assertSee($asset->number);
        $this->get(FixedAssetResource::getUrl('create'))->assertOk();
        $this->get(FixedAssetResource::getUrl('view', ['record' => $asset]))->assertOk();
        $this->get(FixedAssetResource::getUrl('edit', ['record' => $asset]))->assertOk();

        $viewer = $this->createUserFor($this->business, ['access_admin_panel', 'view_finance']);
        $this->actingAs($viewer);

        $this->assertTrue(FixedAssetResource::canAccess());
        $this->assertFalse(FixedAssetResource::canCreate());
        $this->assertTrue(FixedAssetCategoryResource::canAccess());
        $this->assertFalse(FixedAssetCategoryResource::canCreate());
        $this->get(FixedAssetResource::getUrl('index'))->assertOk();
        $this->get(FixedAssetResource::getUrl('create'))->assertForbidden();
    }

    public function test_category_in_use_cannot_be_deleted_and_ledger_accounts_are_protected(): void
    {
        $category = $this->createCategory();
        $this->createAsset($category);

        $this->assertTrue($category->assetAccount->isReferenced());

        $this->expectException(DomainException::class);
        $category->delete();
    }

    public function test_tenant_audit_reports_a_cross_tenant_asset_category_reference(): void
    {
        $foreignCategory = $this->categoryForOtherTenant();

        DB::table('fixed_assets')->insert([
            'business_id' => $this->business->id,
            'fixed_asset_category_id' => $foreignCategory->id,
            'number' => 'FA-2026-AUDIT',
            'name' => 'Invalid cross-tenant asset',
            'status' => FixedAsset::STATUS_DRAFT,
            'depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'useful_life_months' => 36,
            'residual_value_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('fixed_assets')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_polish_and_english_ui_translations_are_complete(): void
    {
        $this->assertSame('Fixed assets', trans('finance.fixed_assets.navigation', locale: 'en'));
        $this->assertSame('Środki trwałe', trans('finance.fixed_assets.navigation', locale: 'pl'));
        $this->assertSame('Asset categories', trans('finance.fixed_asset_categories.navigation', locale: 'en'));
        $this->assertSame('Kategorie środków trwałych', trans('finance.fixed_asset_categories.navigation', locale: 'pl'));
    }

    public function test_fixed_asset_factories_keep_category_and_asset_in_the_same_tenant(): void
    {
        $category = FixedAssetCategory::factory()->create(['business_id' => $this->business->id]);
        $asset = FixedAsset::factory()->create([
            'business_id' => $this->business->id,
            'fixed_asset_category_id' => $category->id,
        ]);

        $this->assertSame($this->business->id, $category->business_id);
        $this->assertSame($this->business->id, $asset->business_id);
        $this->assertSame($category->id, $asset->fixed_asset_category_id);
    }

    private function createCategory(): FixedAssetCategory
    {
        return app(CreateFixedAssetCategoryAction::class)->execute($this->business, $this->user, $this->categoryData());
    }

    private function createAsset(FixedAssetCategory $category): FixedAsset
    {
        return app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $category->id,
            'name' => 'Test asset',
        ]);
    }

    private function categoryForOtherTenant(): FixedAssetCategory
    {
        $other = $this->createBusiness('Foreign Asset Books', 'foreign-asset-books-'.uniqid());
        $otherUser = $this->createUserFor($other, ['manage_finance']);
        app(InitializeAccountingSetupAction::class)->execute($other, $otherUser);

        return app(CreateFixedAssetCategoryAction::class)->execute($other, $otherUser, $this->categoryData($other));
    }

    /** @return array<string, mixed> */
    private function categoryData(?Business $business = null): array
    {
        $business ??= $this->business;

        return [
            'code' => 'equipment',
            'name' => ['en' => 'Equipment', 'pl' => 'Wyposażenie'],
            'asset_account_id' => $this->account($business, '1500')->id,
            'accumulated_depreciation_account_id' => $this->account($business, '1600')->id,
            'depreciation_expense_account_id' => $this->account($business, '7000')->id,
            'default_depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'default_useful_life_months' => 36,
            'default_residual_value_percent' => 0,
            'is_active' => true,
        ];
    }

    private function account(Business $business, string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    /** @param list<string> $permissions */
    private function createUserFor(Business $business, array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function createBusiness(string $name, string $slug): Business
    {
        return Business::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
    }
}
