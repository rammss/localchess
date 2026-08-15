<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/components/Pagination.vue';
import { Card, CardContent } from '@/components/ui/card';
import * as tournamentRoutes from '@/routes/public/tournaments';
import type { Paginated, PublicTeam, Tournament } from '@/types';

const props = defineProps<{
    team: PublicTeam;
    tournaments: Paginated<Tournament>;
}>();
</script>

<template>
    <Head :title="`Torneos — ${props.team.name}`" />

    <div class="flex flex-col space-y-6">
        <h1 class="text-xl font-semibold">Torneos de {{ props.team.name }}</h1>

        <Card v-if="props.tournaments.data.length === 0">
            <CardContent class="py-6 text-sm text-muted-foreground"
                >Todavía no hay torneos procesados.</CardContent
            >
        </Card>

        <div v-else class="flex flex-col gap-3">
            <Link
                v-for="tournament in props.tournaments.data"
                :key="tournament.id"
                :href="
                    tournamentRoutes.show.url([props.team.slug, tournament.id])
                "
            >
                <Card class="transition-colors hover:bg-accent/50">
                    <CardContent class="flex items-center justify-between py-4">
                        <div>
                            <p class="font-medium">{{ tournament.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    [tournament.place, tournament.country]
                                        .filter(Boolean)
                                        .join(', ')
                                }}
                            </p>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ tournament.begins }} — {{ tournament.ends }}
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <Pagination :links="props.tournaments.links" />
    </div>
</template>
