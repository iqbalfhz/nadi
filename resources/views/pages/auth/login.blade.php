<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-1 text-center lg:text-left">
            <flux:heading size="xl">{{ __('Selamat datang kembali') }}</flux:heading>
            <flux:subheading>{{ __('Masuk dengan akun NADI Anda untuk melanjutkan.') }}</flux:subheading>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center lg:text-left" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="nama@tangcity.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Lupa password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Ingat saya')" :checked="old('remember')" />

            <button
                type="submit"
                data-test="login-button"
                class="w-full rounded-lg bg-[#FFB020] px-4 py-2.5 text-sm font-semibold text-[#101827] transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#FFB020] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-neutral-950"
            >
                {{ __('Masuk') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
