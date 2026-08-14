<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-1 text-center lg:text-left">
            <flux:heading size="xl">{{ __('Konfirmasi Password') }}</flux:heading>
            <flux:subheading>{{ __('Ini area aman — konfirmasi password Anda dulu untuk melanjutkan.') }}</flux:subheading>
        </div>

        <x-auth-session-status class="text-center lg:text-left" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Konfirmasi dengan passkey')"
            :loading-label="__('Mengonfirmasi...')"
            :separator="__('Atau konfirmasi dengan password')"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <button
                type="submit"
                data-test="confirm-password-button"
                class="w-full rounded-lg bg-[#FFB020] px-4 py-2.5 text-sm font-semibold text-[#101827] transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#FFB020] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-neutral-950"
            >
                {{ __('Konfirmasi') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
