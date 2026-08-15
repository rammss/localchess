<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import * as competitionRoutes from '@/routes/public/competitions';
import type { Competition, Paginated, PublicTeam } from '@/types';

const props = defineProps<{
    team: PublicTeam;
    competitions: Paginated<Competition>;
}>();
</script>

<template>
    <Head :title="`Competencias — ${props.team.name}`" />

    <div class="flex flex-col space-y-6">
        <h1 class="text-xl font-semibold">
            Competencias de {{ props.team.name }}
        </h1>

        <Card v-if="props.competitions.data.length === 0">
            <CardContent class="py-6 text-sm text-muted-foreground"
                >Todavía no hay competencias públicas.</CardContent
            >
        </Card>

        <div v-else class="flex flex-col gap-3">
            <Link
                v-for="competition in props.competitions.data"
                :key="competition.id"
                :href="
                    competitionRoutes.show.url([
                        props.team.slug,
                        competition.id,
                    ])
                "
            >
                <Card class="transition-colors hover:bg-accent/50">
                    <CardContent class="flex items-center justify-between py-4">
                        <p class="font-medium">{{ competition.name }}</p>
                        <Badge
                            :variant="
                                competition.in_progress ? 'default' : 'outline'
                            "
                        >
                            {{
                                competition.in_progress
                                    ? 'En curso'
                                    : 'Finalizada'
                            }}
                        </Badge>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <Pagination :links="props.competitions.links" />
    </div>
</template>
