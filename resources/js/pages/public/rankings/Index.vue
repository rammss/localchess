<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import * as rankingRoutes from '@/routes/public/rankings';
import type { PublicTeam, Ranking } from '@/types';

const props = defineProps<{
    team: PublicTeam;
    rankings: Pick<Ranking, 'id' | 'name' | 'acronym' | 'is_default'>[];
}>();
</script>

<template>
    <Head :title="`Rankings — ${props.team.name}`" />

    <div class="flex flex-col space-y-6">
        <h1 class="text-xl font-semibold">Rankings de {{ props.team.name }}</h1>

        <Card v-if="props.rankings.length === 0">
            <CardContent class="py-6 text-sm text-muted-foreground"
                >Todavía no hay rankings públicos.</CardContent
            >
        </Card>

        <div v-else class="grid gap-4 sm:grid-cols-2">
            <Link
                v-for="ranking in props.rankings"
                :key="ranking.id"
                :href="rankingRoutes.show.url([props.team.slug, ranking.id])"
            >
                <Card class="transition-colors hover:bg-accent/50">
                    <CardContent class="flex items-center justify-between py-4">
                        <div>
                            <p class="font-medium">{{ ranking.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ ranking.acronym }}
                            </p>
                        </div>
                        <Badge v-if="ranking.is_default" variant="secondary"
                            >Por defecto</Badge
                        >
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
