<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <Button
                v-if="link.url"
                as-child
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
            >
                <Link :href="link.url" preserve-scroll>
                    <span v-html="link.label" />
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                <span v-html="link.label" />
            </Button>
        </template>
    </nav>
</template>
