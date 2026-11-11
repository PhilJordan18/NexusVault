import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type Replacements = Record<string, string | number>;

/**
 * Mirror of Laravel's __() helper for the JSON catalog shared by
 * HandleInertiaRequests: looks the key up in lang/{locale}.json and
 * replaces :placeholders.
 */
export function useTranslations() {
    const page = usePage();

    const locale = computed(() => page.props.locale);

    function translate(key: string, replacements: Replacements = {}): string {
        let value = page.props.translations[key] ?? key;

        for (const [name, replacement] of Object.entries(replacements)) {
            value = value.split(`:${name}`).join(String(replacement));
        }

        return value;
    }

    return { locale, t: translate };
}
