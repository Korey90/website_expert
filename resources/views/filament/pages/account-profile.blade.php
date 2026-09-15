<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ─── Section 1: Personal Profile ──────────────────────────────── --}}
        <form wire:submit.prevent="saveProfile" class="space-y-6">
            {{ $this->profileForm }}

            <div>
                <x-filament::button type="submit" icon="heroicon-o-check">
                    {{ __('account.save_profile') }}
                </x-filament::button>
            </div>
        </form>

        {{-- ─── Section 2: Change Password ────────────────────────────────── --}}
        <form wire:submit.prevent="changePassword" class="space-y-6">
            {{ $this->passwordForm }}

            <div>
                <x-filament::button type="submit" icon="heroicon-o-lock-closed" color="warning">
                    {{ __('account.change_password') }}
                </x-filament::button>
            </div>
        </form>

        {{-- ─── Section 3: Two-Factor Authentication ──────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">{{ __('account.section_2fa') }}</x-slot>
            <x-slot name="description">{{ __('account.section_2fa_desc') }}</x-slot>

            {{ $this->securityForm }}
        </x-filament::section>

    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
