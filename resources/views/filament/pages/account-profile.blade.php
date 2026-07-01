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

            @if (auth()->user()->two_factor_enabled)
                <div class="flex items-center gap-3 mb-4">
                    <x-filament::badge color="success" icon="heroicon-o-shield-check">
                        {{ __('account.2fa_active') }}
                    </x-filament::badge>
                </div>

                <form wire:submit.prevent="disable2fa" class="space-y-4">
                    {{ $this->twoFactorForm }}

                    <x-filament::button type="submit" icon="heroicon-o-shield-exclamation" color="danger">
                        {{ __('account.2fa_disable') }}
                    </x-filament::button>
                </form>
            @elseif ($this->showQrStep)
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('account.2fa_scan_instruction') }}
                    </p>

                    {{-- QR Code SVG --}}
                    <div class="inline-block p-3 bg-white rounded-lg shadow-sm border border-gray-200">
                        {!! $this->twoFactorQrSvg !!}
                    </div>

                    {{-- Manual entry key --}}
                    @if ($this->twoFactorSecret)
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-medium">{{ __('account.2fa_manual_key') }}:</span>
                            <code class="ml-2 font-mono bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs tracking-widest select-all">
                                {{ $this->twoFactorSecret }}
                            </code>
                        </div>
                    @endif

                    <form wire:submit.prevent="confirm2fa" class="space-y-4">
                        {{ $this->twoFactorForm }}

                        <div class="flex gap-3">
                            <x-filament::button type="submit" icon="heroicon-o-check-badge" color="success">
                                {{ __('account.2fa_confirm') }}
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                wire:click="$set('showQrStep', false)"
                                color="gray"
                                outlined
                            >
                                {{ __('account.cancel') }}
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @else
                <div class="flex items-center gap-3 mb-4">
                    <x-filament::badge color="gray" icon="heroicon-o-shield-exclamation">
                        {{ __('account.2fa_inactive') }}
                    </x-filament::badge>
                </div>

                <x-filament::button
                    type="button"
                    wire:click="initiate2fa"
                    icon="heroicon-o-qr-code"
                    color="primary"
                >
                    {{ __('account.2fa_enable') }}
                </x-filament::button>
            @endif
        </x-filament::section>

    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
