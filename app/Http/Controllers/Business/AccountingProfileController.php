<?php

namespace App\Http\Controllers\Business;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\UpdateAccountingProfileAction;
use App\Http\Controllers\Portal\BasePortalController;
use App\Http\Requests\Business\UpdateAccountingProfileRequest;
use App\Models\AccountingProfile;
use App\Models\Business;
use App\Support\PermissionHelper;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountingProfileController extends BasePortalController
{
    public function __construct(
        private readonly InitializeAccountingSetupAction $initializeAccountingSetup,
        private readonly UpdateAccountingProfileAction $updateAccountingProfile,
    ) {}

    public function edit(): Response
    {
        $business = currentBusiness();
        abort_unless($business instanceof Business, 404);
        $this->authorizeManagement($business);

        $profile = $this->initializeAccountingSetup->execute($business, auth()->user())['profile'];

        return Inertia::render('Business/AccountingProfile', [
            'profile' => $this->profileForFrontend($profile),
            'business' => $business->only(['id', 'name']),
            'client' => $this->clientForUser()?->only('id', 'company_name'),
        ]);
    }

    public function update(UpdateAccountingProfileRequest $request): RedirectResponse
    {
        $business = currentBusiness();
        abort_unless($business instanceof Business, 404);
        $profile = $this->initializeAccountingSetup->execute($business, $request->user())['profile'];

        $this->updateAccountingProfile->execute(
            $profile,
            $request->user(),
            $request->validated(),
        );

        return redirect()->back()->with('success', __('finance.accounting.saved'));
    }

    private function authorizeManagement(Business $business): void
    {
        $canManage = auth()->user()
            ?->businessMemberships()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        abort_unless($canManage && PermissionHelper::allows(auth()->user(), 'manage_finance'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function profileForFrontend(AccountingProfile $profile): array
    {
        return [
            ...$profile->only([
                'id',
                'entity_type',
                'legal_name',
                'company_number',
                'registered_address',
                'functional_currency',
                'accounting_basis',
                'vat_registered',
                'vat_number',
                'vat_scheme',
            ]),
            'financial_year_start' => $profile->financial_year_start?->toDateString(),
            'financial_year_end' => $profile->financial_year_end?->toDateString(),
            'vat_effective_from' => $profile->vat_effective_from?->toDateString(),
            'vat_effective_to' => $profile->vat_effective_to?->toDateString(),
            'has_company_utr' => filled($profile->company_utr),
            'has_paye_reference' => filled($profile->paye_reference),
            'has_accounts_office_reference' => filled($profile->accounts_office_reference),
        ];
    }
}
