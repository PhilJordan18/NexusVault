<x-layouts.auth>
    <div class="card rounded-3xl p-8 max-w-md mx-auto">

        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                <i class="fa-solid fa-key text-5xl text-emerald-500"></i>
            </div>
        </div>

        <h1 class="text-3xl font-semibold text-center mb-1">Forgot Password?</h1>
        <p class="text-[var(--text-secondary)] text-center mb-8">We'll send you a reset link</p>

        @if (session('status'))
            <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-500 text-center">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-6">
                <label class="block text-sm text-[var(--text-secondary)] mb-1.5">Email address</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full bg-[var(--bg-input)] border border-[var(--border-color)] focus:border-emerald-500 rounded-2xl py-4 px-5 outline-none">
                @error('email')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-2xl text-lg transition">
                Send Reset Link
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] text-sm">← Back to login</a>
        </div>
    </div>
</x-layouts.auth>
