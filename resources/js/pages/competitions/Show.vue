<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import * as competitionRoutes from '@/routes/competitions';
import type {
    Competition,
    CompetitionStanding,
    Paginated,
    Team,
} from '@/types';

const props = defineProps<{
    competition: Competition & {
        ranking: { id: number; name: string; acronym: string } | null;
    };
    standings: Paginated<CompetitionStanding>;
}>();

const page = usePage<{ currentTeam: Team }>();

const speedLabels: Record<Competition['speed'], string> = {
    standard: 'Clásica',
    rapid: 'Rápida',
    blitz: 'Blitz',
};

const formatNumber = (value: number | null) =>
    value === null
        ? '—'
        : value.toLocaleString('es-AR', { maximumFractionDigits: 1 });

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Competencias',
                href: competitionRoutes.index(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head :title="props.competition.name" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                :title="props.competition.name"
                description="Tabla de posiciones acumuladas"
            />
            <Button as-child variant="outline" size="sm">
                <Link
                    :href="
                        competitionRoutes.edit([
                            page.props.currentTeam.slug,
                            props.competition.id,
                        ])
                    "
                >
                    <Pencil class="h-4 w-4" /> Editar
                </Link>
            </Button>
        </div>

        <div
            class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            <Badge
                :variant="props.competition.in_progress ? 'default' : 'outline'"
            >
                {{ props.competition.in_progress ? 'En curso' : 'Finalizada' }}
            </Badge>
            <span>{{ speedLabels[props.competition.speed] }}</span>
            <span v-if="props.competition.ranking"
                >· Ranking: {{ props.competition.ranking.name }}</span
            >
            <span v-if="props.competition.discarded_games > 0">
                · Descarta {{ props.competition.discarded_games }} torneo(s)
            </span>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">Pos.</TableHead>
                        <TableHead>Jugador</TableHead>
                        <TableHead class="text-right"
                            >Torneos jugados</TableHead
                        >
                        <TableHead class="text-right">Descartados</TableHead>
                        <TableHead class="text-right">Puntaje</TableHead>
                        <TableHead class="text-right">Performance</TableHead>
                        <TableHead class="text-right">Variación ELO</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.standings.data.length === 0"
                        :colspan="7"
                    >
                        Todavía no hay jugadores con resultados en esta
                        competencia.
                    </TableEmpty>
                    <TableRow
                        v-for="standing in props.standings.data"
                        :key="standing.id"
                    >
                        <TableCell class="font-medium">{{
                            standing.position ?? '—'
                        }}</TableCell>
                        <TableCell>
                            <span v-if="standing.player"
                                >{{ standing.player.last_name }},
                                {{ standing.player.name }}</span
                            >
                            <span v-else class="text-muted-foreground"
                                >Jugador eliminado</span
                            >
                        </TableCell>
                        <TableCell class="text-right">{{
                            standing.played_events ?? 0
                        }}</TableCell>
                        <TableCell class="text-right">{{
                            standing.discarded_tournaments ?? 0
                        }}</TableCell>
                        <TableCell class="text-right font-medium">{{
                            formatNumber(standing.scoreboard)
                        }}</TableCell>
                        <TableCell class="text-right">{{
                            formatNumber(standing.performance)
                        }}</TableCell>
                        <TableCell class="text-right">
                            <span
                                :class="
                                    (standing.variation ?? 0) >= 0
                                        ? 'text-emerald-600'
                                        : 'text-red-600'
                                "
                            >
                                {{ (standing.variation ?? 0) >= 0 ? '+' : ''
                                }}{{ formatNumber(standing.variation) }}
                            </span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.standings.links" />
    </div>
</template>
