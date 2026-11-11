<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/layouts/LegalLayout.vue';
import type { LegalPageName, LegalSection } from '@/types';

defineProps<{
    page: LegalPageName;
    title: string;
    description: string;
    lastUpdated: string;
    sections: LegalSection[];
}>();
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
    </Head>

    <LegalLayout
        :title="title"
        :description="description"
        :last-updated="lastUpdated"
    >
        <section
            v-for="(section, index) in sections"
            :key="index"
            :aria-labelledby="`${page}-section-${index}`"
            class="space-y-3"
        >
            <h2
                :id="`${page}-section-${index}`"
                class="text-2xl font-semibold text-[var(--text-primary)]"
            >
                {{ section.heading }}
            </h2>
            <template
                v-for="(paragraph, paragraphIndex) in section.paragraphs"
                :key="paragraphIndex"
            >
                <p v-if="typeof paragraph === 'string'">{{ paragraph }}</p>
                <p v-else>
                    {{ paragraph.text }}
                    <a
                        :href="`mailto:${paragraph.email}`"
                        class="rounded text-emerald-400 hover:text-emerald-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500"
                        >{{ paragraph.email }}</a
                    >{{ paragraph.suffix }}
                </p>
            </template>
        </section>
    </LegalLayout>
</template>
