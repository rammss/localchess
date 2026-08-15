<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as rankingRoutes from '@/routes/rankings';
import type { Ranking, Team } from '@/types';

const props = defineProps<{
    ranking: Ranking;
}>();

const page = usePage<{ currentTeam: Team }>();
const deleting = ref(false);

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Rankings',
                href: rankingRoutes.index(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head :title="props.ranking.name" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                :title="props.ranking.name"
                description="Editar ranking"
            />
            <Button variant="destructive" size="sm" @click="deleting = true">
                <Trash2 class="h-4 w-4" /> Eliminar
            </Button>
        </div>

        <Form
            v-bind="
                rankingRoutes.update.form([
                    page.props.currentTeam.slug,
                    props.ranking.id,
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
                        :default-value="props.ranking.name"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="acronym">Sigla</Label>
                    <Input
                        id="acronym"
                        name="acronym"
                        required
                        :default-value="props.ranking.acronym"
                    />
                    <InputError :message="errors.acronym" />
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_default"
                        name="is_default"
                        :default-value="props.ranking.is_default"
                    />
                    <Label for="is_default"
                        >Ranking por defecto de la federación</Label
                    >
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="state"
                        name="state"
                        :default-value="props.ranking.state"
                    />
                    <Label for="state">Activo</Label>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="ranking_base">ELO base de entrada</Label>
                    <Input
                        id="ranking_base"
                        name="ranking_base"
                        type="number"
                        :default-value="props.ranking.ranking_base"
                        required
                    />
                    <InputError :message="errors.ranking_base" />
                </div>
                <div class="grid gap-2">
                    <Label for="ranking_restart">Umbral de reinicio</Label>
                    <Input
                        id="ranking_restart"
                        name="ranking_restart"
                        type="number"
                        :default-value="props.ranking.ranking_restart"
                        required
                    />
                    <InputError :message="errors.ranking_restart" />
                </div>
                <div class="grid gap-2">
                    <Label for="ranking_max_entry">Tope de entrada</Label>
                    <Input
                        id="ranking_max_entry"
                        name="ranking_max_entry"
                        type="number"
                        :default-value="props.ranking.ranking_max_entry"
                        required
                    />
                    <InputError :message="errors.ranking_max_entry" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="k_base">K base</Label>
                    <Input
                        id="k_base"
                        name="k_base"
                        type="number"
                        :default-value="props.ranking.k_base"
                        required
                    />
                    <InputError :message="errors.k_base" />
                </div>
                <div class="grid gap-2">
                    <Label for="k_rapid">K rápidas</Label>
                    <Input
                        id="k_rapid"
                        name="k_rapid"
                        type="number"
                        step="0.1"
                        :default-value="props.ranking.k_rapid"
                        required
                    />
                    <InputError :message="errors.k_rapid" />
                </div>
                <div class="grid gap-2">
                    <Label for="k_blitz">K blitz</Label>
                    <Input
                        id="k_blitz"
                        name="k_blitz"
                        type="number"
                        step="0.1"
                        :default-value="props.ranking.k_blitz"
                        required
                    />
                    <InputError :message="errors.k_blitz" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="valuation_games_standard"
                        >Partidas mínimas — clásica</Label
                    >
                    <Input
                        id="valuation_games_standard"
                        name="valuation_games_standard"
                        type="number"
                        :default-value="props.ranking.valuation_games_standard"
                        required
                    />
                    <InputError :message="errors.valuation_games_standard" />
                </div>
                <div class="grid gap-2">
                    <Label for="valuation_games_rapid"
                        >Partidas mínimas — rápidas</Label
                    >
                    <Input
                        id="valuation_games_rapid"
                        name="valuation_games_rapid"
                        type="number"
                        :default-value="props.ranking.valuation_games_rapid"
                        required
                    />
                    <InputError :message="errors.valuation_games_rapid" />
                </div>
                <div class="grid gap-2">
                    <Label for="valuation_games_blitz"
                        >Partidas mínimas — blitz</Label
                    >
                    <Input
                        id="valuation_games_blitz"
                        name="valuation_games_blitz"
                        type="number"
                        :default-value="props.ranking.valuation_games_blitz"
                        required
                    />
                    <InputError :message="errors.valuation_games_blitz" />
                </div>
            </div>

            <Button type="submit" :disabled="processing"
                >Guardar cambios</Button
            >
        </Form>
    </div>

    <ConfirmDeleteDialog
        :open="deleting"
        title="¿Eliminar este ranking?"
        :description="`Se eliminará '${props.ranking.name}'. Esta acción no se puede deshacer.`"
        :form="
            rankingRoutes.destroy.form([
                page.props.currentTeam.slug,
                props.ranking.id,
            ])
        "
        @update:open="(open) => (deleting = open)"
    />
</template>
