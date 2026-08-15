<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as competitionRoutes from '@/routes/competitions';
import type { Ranking, Team } from '@/types';

const props = defineProps<{
    rankings: Pick<Ranking, 'id' | 'name' | 'acronym'>[];
}>();

const page = usePage<{ currentTeam: Team }>();

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 md:text-sm';

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Competencias',
                href: competitionRoutes.index(layoutProps.currentTeam!.slug),
            },
            {
                title: 'Nueva',
                href: competitionRoutes.create(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Nueva competencia" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            title="Nueva competencia"
            description="Un circuito de torneos con puntaje acumulado y descartes"
        />

        <Form
            v-bind="competitionRoutes.store.form(page.props.currentTeam.slug)"
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
                        placeholder="Circuito Apertura 2026"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="acronym">Sigla</Label>
                    <Input id="acronym" name="acronym" placeholder="CA26" />
                    <InputError :message="errors.acronym" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="url">URL pública</Label>
                <Input
                    id="url"
                    name="url"
                    required
                    placeholder="circuito-apertura-2026"
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
                        <option value="standard">Clásica</option>
                        <option value="rapid">Rápida</option>
                        <option value="blitz">Blitz</option>
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
                        default-value="0"
                        required
                    />
                    <InputError :message="errors.discarded_games" />
                </div>
                <div class="flex items-center gap-2 self-end pb-2">
                    <Checkbox
                        id="in_progress"
                        name="in_progress"
                        :default-value="true"
                    />
                    <Label for="in_progress">En curso</Label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="begins">Comienza</Label>
                    <Input id="begins" name="begins" type="date" />
                    <InputError :message="errors.begins" />
                </div>
                <div class="grid gap-2">
                    <Label for="ends">Finaliza</Label>
                    <Input id="ends" name="ends" type="date" />
                    <InputError :message="errors.ends" />
                </div>
            </div>

            <Button type="submit" :disabled="processing"
                >Crear competencia</Button
            >
        </Form>
    </div>
</template>
