<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="NexusVault" />

    <main class="flex min-h-screen items-center justify-center bg-zinc-950 px-6 py-16 text-white">
        <section class="w-full max-w-3xl text-center">
            <img src="/logo/LogoMonogramme.svg" alt="" class="mx-auto h-20 w-20" />

            <p class="mt-8 text-sm font-semibold text-emerald-400">NexusVault</p>
            <h1 class="mt-4 text-5xl font-semibold leading-tight">Secure password vault</h1>
            <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-zinc-300">
                Your encrypted vault, secure sharing, MFA, and passkeys in one clean workspace.
            </p>

            <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                <a
                    href="/login"
                    class="inline-flex min-h-12 items-center justify-center rounded-md bg-emerald-500 px-6 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400"
                >
                    Log in
                </a>
                <a
                    href="/register"
                    class="inline-flex min-h-12 items-center justify-center rounded-md border border-white/15 px-6 text-sm font-semibold text-white transition hover:bg-white/10"
                >
                    Create account
                </a>
            </div>
        </section>
    </main>
</template>
