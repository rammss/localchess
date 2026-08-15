<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';
import * as rankingRoutes from '@/routes/public/rankings';
import type {
    Paginated,
    PublicTeam,
    Ranking,
    RankingStandingRow,
    Speed,
} from '@/types';

const props = defineProps<{
    team: PublicTeam;
    ranking: Pick<Ranking, 'id' | 'name' | 'acronym'>;
    players: Paginated<RankingStandingRow>;
    speed: Speed;
    search: string | null;
}>();

const { search } = useDebouncedSearch(
    rankingRoutes.show.url([props.team.slug, props.ranking.id], {
        query: { speed: props.speed },
    }),
    props.search,
);

const speedLabels: Record<Speed, string> = {
    standard: 'Clásica',
    rapid: 'Rápida',
    blitz: 'Blitz',
};
const eloColumn = (row: RankingStandingRow) => row[`${props.speed}_elo`];
</script>

<template>
    <Head :title="`${props.ranking.name} — ${props.team.name}`" />

    <div class="flex flex-col space-y-6">
        <div>
            <Link
                :href="rankingRoutes.index.url(props.team.slug)"
                class="text-sm text-muted-foreground hover:underline"
            >
                ‹ Rankings
            </Link>
            <h1 class="text-xl font-semibold">{{ props.ranking.name }}</h1>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex gap-2">
                <Button
                    v-for="s in ['standard', 'rapid', 'blitz'] as Speed[]"
                    :key="s"
                    :variant="s === props.speed ? 'default' : 'outline'"
                    size="sm"
                    as-child
                >
                    <Link
                        :href="
                            rankingRoutes.show.url(
                                [props.team.slug, props.ranking.id],
                                { query: { speed: s, search: props.search } },
                            )
                        "
                    >
                        {{ speedLabels[s] }}
                    </Link>
                </Button>
            </div>
            <Input
                v-model="search"
                placeholder="Buscar jugador…"
                class="max-w-xs"
            />
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">Nº</TableHead>
                        <TableHead>Jugador</TableHead>
                        <TableHead>Título</TableHead>
                        <TableHead class="text-right"
                            >ELO {{ speedLabels[props.speed] }}</TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.players.data.length === 0"
                        :colspan="4"
                    >
                        No hay jugadores en esta modalidad todavía.
                    </TableEmpty>
                    <TableRow v-for="row in props.players.data" :key="row.id">
                        <TableCell>{{ row.id_on_ranking }}</TableCell>
                        <TableCell class="font-medium"
                            >{{ row.player.last_name }},
                            {{ row.player.name }}</TableCell
                        >
                        <TableCell>
                            <Badge
                                v-if="row.player.title"
                                variant="secondary"
                                >{{ row.player.title }}</Badge
                            >
                        </TableCell>
                        <TableCell class="text-right font-medium">{{
                            eloColumn(row)
                        }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.players.links" />
    </div>
</template>
