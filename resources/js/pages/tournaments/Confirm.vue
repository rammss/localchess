<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import TournamentController from '@/actions/App/Http/Controllers/Tournaments/TournamentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Competition, Team, Tournament, TournamentMatch } from '@/types';

const props = defineProps<{
    tournament: Tournament;
    matches: TournamentMatch[];
    competitions: Pick<Competition, 'id' | 'name' | 'acronym'>[];
}>();

const page = usePage<{ currentTeam: Team }>();

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 md:text-sm';

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Torneos',
                href: TournamentController.index(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Confirmar torneo" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="props.tournament.name"
            description="Revisá qué jugadores del archivo TRF16 se emparejaron con la base de datos antes de procesar el torneo"
        />

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">Nº</TableHead>
                        <TableHead>Nombre (TRF16)</TableHead>
                        <TableHead class="text-right">ELO TRF16</TableHead>
                        <TableHead>Coincidencia</TableHead>
                        <TableHead>Sincronizar datos</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="props.matches.length === 0" :colspan="5">
                        El archivo no contiene jugadores.
                    </TableEmpty>
                    <TableRow
                        v-for="match in props.matches"
                        :key="match.startingRank"
                    >
                        <TableCell>{{ match.startingRank }}</TableCell>
                        <TableCell
                            >{{ match.lastName }}, {{ match.name }}</TableCell
                        >
                        <TableCell class="text-right">{{
                            match.rating || '—'
                        }}</TableCell>
                        <TableCell>
                            <template v-if="match.matchedPlayerId">
                                <Badge variant="default">{{
                                    match.matchedPlayerName
                                }}</Badge>
                                <span
                                    v-if="match.similarity !== null"
                                    class="ml-1 text-xs text-muted-foreground"
                                >
                                    {{ Math.round(match.similarity) }}%
                                    similitud
                                </span>
                            </template>
                            <Badge v-else variant="outline"
                                >Jugador nuevo</Badge
                            >
                        </TableCell>
                        <TableCell>
                            <label
                                v-if="match.matchedPlayerId"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="sync_starting_ranks[]"
                                    :value="match.startingRank"
                                    form="process-tournament-form"
                                />
                                Sobrescribir nombre/fecha de nacimiento
                            </label>
                            <span v-else class="text-xs text-muted-foreground"
                                >Se creará automáticamente</span
                            >
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Form
            id="process-tournament-form"
            v-bind="
                TournamentController.process.form([
                    page.props.currentTeam.slug,
                    props.tournament.id,
                ])
            "
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="speed">Modalidad</Label>
                    <select
                        id="speed"
                        name="speed"
                        required
                        :class="selectClass"
                    >
                        <option value="standard">Clásica</option>
                        <option value="rapid">Rápida</option>
                        <option value="blitz">Blitz</option>
                    </select>
                    <InputError :message="errors.speed" />
                </div>
                <div class="grid gap-2">
                    <Label for="competition_id">Competencia (opcional)</Label>
                    <select
                        id="competition_id"
                        name="competition_id"
                        :class="selectClass"
                    >
                        <option value="">Sin competencia</option>
                        <option
                            v-for="competition in props.competitions"
                            :key="competition.id"
                            :value="competition.id"
                        >
                            {{ competition.name }}
                        </option>
                    </select>
                    <InputError :message="errors.competition_id" />
                </div>
            </div>

            <Button type="submit" :disabled="processing"
                >Procesar torneo</Button
            >
        </Form>
    </div>
</template>
