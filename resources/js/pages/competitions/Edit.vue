<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { Eye, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as competitionRoutes from '@/routes/competitions';
import type { Competition, Ranking, Team } from '@/types';

const props = defineProps<{
    competition: Competition;
    rankings: Pick<Ranking, 'id' | 'name' | 'acronym'>[];
}>();

const page = usePage<{ currentTeam: Team }>();
const deleting = ref(false);

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 md:text-sm';

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

    <div class="flex max-w-2xl flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                :title="props.competition.name"
                description="Editar competencia"
            />
            <div class="flex gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link
                        :href="
                            competitionRoutes.show([
                                page.props.currentTeam.slug,
                                props.competition.id,
                            ])
                        "
                    >
                        <Eye class="h-4 w-4" /> Ver posiciones
                    </Link>
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="deleting = true"
                >
                    <Trash2 class="h-4 w-4" /> Eliminar
                </Button>
            </div>
        </div>

        <Form
            v-bind="
                competitionRoutes.update.form([
                    page.props.currentTeam.slug,
                    props.competition.id,
                ])
            "
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        :default-value="props.competition.name"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="acronym">Sigla</Label>
                    <Input
                        id="acronym"
                        name="acronym"
                        :default-value="props.competition.acronym ?? ''"
                    />
                    <InputError :message="errors.acronym" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="url">URL pública</Label>
                <Input
                    id="url"
                    name="url"
                    required
                    :default-value="props.competition.url"
                />
                <InputError :message="errors.url" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="ranking_id">Ranking asociado</Label>
                    <select
                        id="ranking_id"
                        name="ranking_id"
                        :class="selectClass"
                    >
                        <option value="">Sin ranking asociado</option>
                        <option
                            v-for="ranking in props.rankings"
                            :key="ranking.id"
                            :value="ranking.id"
                            :selected="
                                ranking.id === props.competition.ranking_id
                            "
                        >
                            {{ ranking.name }} ({{ ranking.acronym }})
                        </option>
                    </select>
                    <InputError :message="errors.ranking_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="speed">Modalidad</Label>
                    <select
                        id="speed"
                        name="speed"
                        required
                        :class="selectClass"
                    >
                        <option
                            value="standard"
                            :selected="props.competition.speed === 'standard'"
                        >
                            Clásica
                        </option>
                        <option
                            value="rapid"
                            :selected="props.competition.speed === 'rapid'"
                        >
                            Rápida
                        </option>
                        <option
                            value="blitz"
                            :selected="props.competition.speed === 'blitz'"
                        >
                            Blitz
                        </option>
                    </select>
                    <InputError :message="errors.speed" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="discarded_games">Torneos a descartar</Label>
                    <Input
                        id="discarded_games"
                        name="discarded_games"
                        type="number"
                        min="0"
                        :default-value="props.competition.discarded_games"
                        required
                    />
                    <InputError :message="errors.discarded_games" />
                </div>
                <div class="flex items-center gap-2 self-end pb-2">
                    <Checkbox
                        id="in_progress"
                        name="in_progress"
                        :default-value="props.competition.in_progress"
                    />
                    <Label for="in_progress">En curso</Label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="begins">Comienza</Label>
                    <Input
                        id="begins"
                        name="begins"
                        type="date"
                        :default-value="props.competition.begins ?? ''"
                    />
                    <InputError :message="errors.begins" />
                </div>
                <div class="grid gap-2">
                    <Label for="ends">Finaliza</Label>
                    <Input
                        id="ends"
                        name="ends"
                        type="date"
                        :default-value="props.competition.ends ?? ''"
                    />
                    <InputError :message="errors.ends" />
                </div>
            </div>

            <Button type="submit" :disabled="processing"
                >Guardar cambios</Button
            >
        </Form>
    </div>

    <ConfirmDeleteDialog
        :open="deleting"
        title="¿Eliminar esta competencia?"
        :description="`Se eliminará '${props.competition.name}'. Esta acción no se puede deshacer.`"
        :form="
            competitionRoutes.destroy.form([
                page.props.currentTeam.slug,
                props.competition.id,
            ])
        "
        @update:open="(open) => (deleting = open)"
    />
</template>
