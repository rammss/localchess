<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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
import * as competitionRoutes from '@/routes/competitions';
import type { Competition, Paginated, Team } from '@/types';

const props = defineProps<{
    competitions: Paginated<Competition>;
    search: string | null;
}>();

const page = usePage<{ currentTeam: Team }>();
const { search } = useDebouncedSearch(
    competitionRoutes.index.url(page.props.currentTeam.slug),
    props.search,
);

const deleting = ref<Competition | null>(null);

const speedLabels: Record<Competition['speed'], string> = {
    standard: 'Clásica',
    rapid: 'Rápida',
    blitz: 'Blitz',
};

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
    <Head title="Competencias" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Competencias"
                description="Circuitos de torneos con puntaje acumulado y descartes"
            />
            <Button as-child>
                <Link
                    :href="
                        competitionRoutes.create(page.props.currentTeam.slug)
                    "
                >
                    <Plus /> Nueva competencia
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
                        <TableHead>Modalidad</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Fechas</TableHead>
                        <TableHead class="text-right">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="props.competitions.data.length === 0"
                        :colspan="5"
                    >
                        No hay competencias todavía.
                    </TableEmpty>
                    <TableRow
                        v-for="competition in props.competitions.data"
                        :key="competition.id"
                    >
                        <TableCell class="font-medium">
                            {{ competition.name }}
                            <Badge
                                v-if="competition.acronym"
                                variant="secondary"
                                class="ml-2"
                            >
                                {{ competition.acronym }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{
                            speedLabels[competition.speed]
                        }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    competition.in_progress
                                        ? 'default'
                                        : 'outline'
                                "
                            >
                                {{
                                    competition.in_progress
                                        ? 'En curso'
                                        : 'Finalizada'
                                }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <span v-if="competition.begins || competition.ends">
                                {{ competition.begins ?? '—' }} /
                                {{ competition.ends ?? '—' }}
                            </span>
                            <span v-else>—</span>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button as-child variant="ghost" size="sm">
                                <Link
                                    :href="
                                        competitionRoutes.show([
                                            page.props.currentTeam.slug,
                                            competition.id,
                                        ])
                                    "
                                >
                                    <Eye class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button as-child variant="ghost" size="sm">
                                <Link
                                    :href="
                                        competitionRoutes.edit([
                                            page.props.currentTeam.slug,
                                            competition.id,
                                        ])
                                    "
                                >
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="deleting = competition"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :links="props.competitions.links" />
    </div>

    <ConfirmDeleteDialog
        v-if="deleting"
        :open="deleting !== null"
        title="¿Eliminar esta competencia?"
        :description="`Se eliminará '${deleting?.name}'. Esta acción no se puede deshacer.`"
        :form="
            competitionRoutes.destroy.form([
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
