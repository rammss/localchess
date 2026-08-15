<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ClipboardCheck, Eye, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import TournamentController from '@/actions/App/Http/Controllers/Tournaments/TournamentController';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import Heading from '@/components/Heading.vue';
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
import type { Paginated, Team, Tournament } from '@/types';

const props = defineProps<{
    tournaments: Paginated<Tournament>;
    search: string | null;
}>();

const page = usePage<{ currentTeam: Team }>();
const { search } = useDebouncedSearch(
    TournamentController.index.url(page.props.currentTeam.slug),
    props.search,
);

const deleting = ref<Tournament | null>(null);

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
    <Head title="Torneos" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Torneos"
                description="Torneos subidos en formato TRF16 y su estado de procesamiento"
            />
            <Button as-child>
                <Link
                    :href="
                        TournamentController.create(page.props.currentTeam.slug)
                    "
                >
                    <Plus /> Subir torneo
                </Link>
            </Button>
        </div>

        <Input
            v-model="search"
            placeholder="Buscar por nombre…"
            class="max-w-sm"
        />

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nombre</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Fechas</TableHead>
                        <TableHead class="text-right">Jugadores</TableHead>
                        <TableHead class="text-right">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.tournaments.data.length === 0"
                        :colspan="5"
                    >
                        No hay torneos todavía.
                    </TableEmpty>
                    <TableRow
                        v-for="tournament in props.tournaments.data"
                        :key="tournament.id"
                    >
                        <TableCell class="font-medium">
                            {{ tournament.name }}
                            <div
                                v-if="tournament.place || tournament.country"
                                class="text-sm text-muted-foreground"
                            >
                                {{
                                    [tournament.place, tournament.country]
                                        .filter(Boolean)
                                        .join(', ')
                                }}
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    tournament.processed ? 'default' : 'outline'
                                "
                            >
                                {{
                                    tournament.processed
                                        ? 'Procesado'
                                        : 'Pendiente de confirmación'
                                }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <span v-if="tournament.begins || tournament.ends">
                                {{ tournament.begins ?? '—' }} /
                                {{ tournament.ends ?? '—' }}
                            </span>
                            <span v-else>—</span>
                        </TableCell>
                        <TableCell class="text-right">{{
                            tournament.amount_players ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right">
                            <template v-if="tournament.processed">
                                <Button as-child variant="ghost" size="sm">
                                    <Link
                                        :href="
                                            TournamentController.show([
                                                page.props.currentTeam.slug,
                                                tournament.id,
                                            ])
                                        "
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </template>
                            <template v-else>
                                <Button as-child variant="ghost" size="sm">
                                    <Link
                                        :href="
                                            TournamentController.confirm([
                                                page.props.currentTeam.slug,
                                                tournament.id,
                                            ])
                                        "
                                    >
                                        <ClipboardCheck class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="deleting = tournament"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </template>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.tournaments.links" />
    </div>

    <ConfirmDeleteDialog
        v-if="deleting"
        :open="deleting !== null"
        title="¿Eliminar este torneo?"
        :description="`Se eliminará '${deleting?.name}'. Esta acción no se puede deshacer.`"
        :form="
            TournamentController.destroy.form([
                page.props.currentTeam.slug,
                deleting.id,
            ])
        "
        @update:open="
            (open) => {
                if (!open) deleting = null;
            }
        "
    />
</template>
