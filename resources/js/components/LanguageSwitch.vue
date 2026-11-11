<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { update } from '@/routes/locale';

const { locale, t } = useTranslations();

const locales = computed(() => [
    { code: 'en', label: t('EN') },
    { code: 'fr', label: t('FR') },
]);

function switchLocale(code: string): void {
    if (code === locale.value) {
        return;
    }

    router.post(update.url(), { locale: code });
}
</script>

<template>
    <div
        role="group"
        :aria-label="t('Language')"
        class="inline-flex h-9 items-center rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] p-1 text-xs font-semibold text-[var(--text-secondary)]"
    >
        <button
            v-for="option in locales"
            :key="option.code"
            type="button"
            :lang="option.code"
            :aria-pressed="option.code === locale"
            class="h-7 min-w-9 rounded-xl px-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500"
            :class="
                option.code === locale
                    ? 'bg-emerald-600 text-white'
                    : 'hover:bg-white/10 hover:text-[var(--text-primary)]'
            "
            @click="switchLocale(option.code)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
