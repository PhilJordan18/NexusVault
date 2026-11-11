<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LanguageSwitch from '@/components/LanguageSwitch.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, home, login, register } from '@/routes';
import { accessibility, cookies, privacy, terms } from '@/routes/legal';

defineProps<{
    title: string;
    description?: string;
    lastUpdated: string;
}>();

const { t } = useTranslations();
const page = usePage();

const isAuthenticated = computed(() => page.props.auth.user !== null);
const currentYear = new Date().getFullYear();

const footerLinks = computed(() => [
    { href: terms.url(), label: t('Terms of Use') },
    { href: privacy.url(), label: t('Privacy Policy') },
    { href: cookies.url(), label: t('Cookie Policy') },
    { href: accessibility.url(), label: t('Accessibility') },
]);

const focusRing =
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500';
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-[var(--bg-primary)] text-[var(--text-primary)]"
    >
        <a
            href="#main-content"
            class="sr-only rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60]"
            :class="focusRing"
        >
            {{ t('Skip to main content') }}
        </a>

        <header
            class="sticky top-0 z-50 border-b border-[var(--border-color)] bg-[var(--bg-secondary)]/90 backdrop-blur-xl"
        >
            <nav
                :aria-label="t('Main navigation')"
                class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6"
            >
                <a
                    :href="home.url()"
                    class="flex min-w-0 items-center gap-3 rounded-2xl"
                    :class="focusRing"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)]"
                    >
                        <img
                            src="/logo/LogoMonogramme.svg"
                            alt=""
                            class="h-6 w-6"
                        />
                    </span>
                    <span
                        class="truncate text-xl font-semibold tracking-normal sm:text-2xl"
                    >
                        NexusVault
                    </span>
                </a>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <LanguageSwitch />

                    <a
                        v-if="isAuthenticated"
                        :href="dashboard.url()"
                        class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        :class="focusRing"
                    >
                        {{ t('Dashboard') }}
                    </a>
                    <template v-else>
                        <a
                            :href="login.url()"
                            class="hidden rounded-2xl px-2 py-1 text-sm text-[var(--text-secondary)] transition hover:text-[var(--text-primary)] sm:inline"
                            :class="focusRing"
                        >
                            {{ t('Log in') }}
                        </a>
                        <a
                            :href="register.url()"
                            class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                            :class="focusRing"
                        >
                            {{ t('Create account') }}
                        </a>
                    </template>
                </div>
            </nav>
        </header>

        <main
            id="main-content"
            tabindex="-1"
            class="mx-auto w-full max-w-4xl flex-1 px-4 py-10 outline-none sm:px-6 sm:py-14"
        >
            <div class="mb-8">
                <p
                    class="mb-3 text-sm font-semibold tracking-[0.24em] text-emerald-400 uppercase"
                >
                    {{ t('Legal Center') }}
                </p>
                <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-4 max-w-2xl text-lg text-[var(--text-secondary)]"
                >
                    {{ description }}
                </p>
                <p class="mt-5 text-sm text-[var(--text-secondary)]">
                    {{ t('Last updated: :date', { date: lastUpdated }) }}
                </p>
            </div>

            <article class="card rounded-2xl p-6 sm:p-8">
                <div class="space-y-8 text-[var(--text-secondary)]">
                    <slot />
                </div>
            </article>
        </main>

        <footer
            class="border-t border-[var(--border-color)] px-4 py-8 text-sm text-[var(--text-secondary)] sm:px-6"
        >
            <div
                class="mx-auto flex max-w-6xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    {{
                        t(
                            '© :year NexusVault. Built with love and strong cryptography.',
                            { year: currentYear },
                        )
                    }}
                </p>
                <nav
                    :aria-label="t('Legal Center')"
                    class="flex flex-wrap gap-x-4 gap-y-2"
                >
                    <Link
                        v-for="link in footerLinks"
                        :key="link.href"
                        :href="link.href"
                        class="rounded transition hover:text-[var(--text-primary)]"
                        :class="focusRing"
                    >
                        {{ link.label }}
                    </Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
