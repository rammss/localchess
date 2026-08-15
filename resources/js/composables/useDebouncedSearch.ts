import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

/**
 * Keeps a `search` query param in sync with an input as the user types,
 * debounced so it doesn't fire a request per keystroke. Replaces the
 * submit-button-only search pattern the 1.0 app used everywhere.
 */
export function useDebouncedSearch(
    url: string,
    initial: string | null,
    delay = 350,
) {
    const search = ref(initial ?? '');
    let timeout: ReturnType<typeof setTimeout> | null = null;

    watch(search, (value) => {
        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(() => {
            router.get(url, value ? { search: value } : {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, delay);
    });

    return { search };
}
